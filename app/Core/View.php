<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    /**
     * @param array<string, mixed> $params
     */
    public static function render(string $template, array $params = [], ?string $layout = 'layout.php'): string
    {
        $viewFile = self::resolvePath($template);
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View {$template} not found.");
        }

        extract($params, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::resolvePath($layout);
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout {$layout} not found.");
        }

        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    private static function resolvePath(string $template): string
    {
        $file = $template;
        if (!str_ends_with($file, '.php')) {
            $file .= '.php';
        }

        return Config::viewPath($file);
    }
}
