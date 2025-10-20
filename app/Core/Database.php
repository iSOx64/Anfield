<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Config::get('DB_HOST', '127.0.0.1'),
            Config::get('DB_PORT', '3306'),
            Config::get('DB_NAME', 'foot_fields')
        );

        try {
            $pdo = new PDO(
                $dsn,
                Config::get('DB_USER', 'root'),
                Config::get('DB_PASS', ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            $message = sprintf('Database connection failed: %s', $exception->getMessage());
            self::logError($message);
            throw $exception;
        }

        self::$connection = $pdo;
        return self::$connection;
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }

    private static function logError(string $message): void
    {
        $logFile = Config::storagePath('logs/app.log');
        $directory = \dirname($logFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $line = sprintf("[%s] %s\n", date('c'), $message);
        file_put_contents($logFile, $line, FILE_APPEND);
    }
}
