<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

class Config
{
    private static array $data = [];
    private static string $basePath;
    private static bool $booted = false;

    public static function boot(string $basePath): void
    {
        if (self::$booted) {
            return;
        }

        self::$basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        if (is_file(self::$basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable(self::$basePath, '.env');
            $dotenv->safeLoad();
        }

        self::$data = $_ENV + $_SERVER;
        self::$booted = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$data[$key] ?? $default;
    }

    public static function basePath(string $append = ''): string
    {
        return self::path(self::$basePath, $append);
    }

    public static function viewPath(string $append = ''): string
    {
        return self::path(self::$basePath . '/views', $append);
    }

    public static function storagePath(string $append = ''): string
    {
        return self::path(self::$basePath . '/storage', $append);
    }

    private static function path(string $base, string $append): string
    {
        if ($append === '') {
            return $base;
        }

        return $base . DIRECTORY_SEPARATOR . ltrim($append, DIRECTORY_SEPARATOR);
    }
}
