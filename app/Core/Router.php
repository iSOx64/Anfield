<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionMethod;

class Router
{
    /**
     * @var array<int, array{httpMethod:string,pattern:string,handler:mixed,parameters:array<int,string>}>
     */
    private array $routes = [];

    public function get(string $pattern, mixed $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, mixed $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, mixed $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, mixed $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    public function dispatch(string $httpMethod, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        foreach ($this->routes as $route) {
            if ($route['httpMethod'] !== $httpMethod) {
                continue;
            }

            $pattern = '#^' . $route['pattern'] . '$#';
            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $parameters = [];
            foreach ($route['parameters'] as $index => $name) {
                $parameters[$name] = $matches[$index] ?? null;
            }

            $this->invokeHandler($route['handler'], $parameters);
            return;
        }

        http_response_code(404);
        echo View::render('errors/404.php', ['path' => $path], null);
    }

    private function addRoute(string $method, string $pattern, mixed $handler): void
    {
        [$regex, $parameters] = $this->compilePattern($pattern);
        $this->routes[] = [
            'httpMethod' => strtoupper($method),
            'pattern' => $regex,
            'handler' => $handler,
            'parameters' => $parameters,
        ];
    }

    /**
     * @return array{0:string,1:array<int,string>}
     */
    private function compilePattern(string $pattern): array
    {
        $parameterNames = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#',
            static function (array $matches) use (&$parameterNames): string {
                $parameterNames[] = $matches[1];
                return '([^/]+)';
            },
            $pattern
        );

        return [$regex ?? $pattern, $parameterNames];
    }

    /**
     * @param mixed $handler
     * @param array<string, mixed> $parameters
     */
    private function invokeHandler(mixed $handler, array $parameters): void
    {
        if ($handler instanceof Closure) {
            echo $handler($parameters);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = $this->resolveController($class);
            echo $instance->$method(...array_values($parameters));
            return;
        }

        if (is_string($handler) && class_exists($handler)) {
            $instance = $this->resolveController($handler);
            if (method_exists($instance, '__invoke')) {
                echo $instance(...array_values($parameters));
                return;
            }
        }

        throw new \InvalidArgumentException('Route handler not supported.');
    }

    private function resolveController(string $class): object
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Controller {$class} not found.");
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Controller {$class} is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if (!$constructor instanceof ReflectionMethod || $constructor->getNumberOfParameters() === 0) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type === null) {
                throw new \RuntimeException("Cannot resolve controller dependency for {$class}.");
            }

            $dependencyClass = $type->getName();
            $dependencies[] = new $dependencyClass();
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
