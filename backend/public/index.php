<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $filePath = __DIR__ . '/../src/' . $classPath . '.php';

    if (is_file($filePath)) {
        require_once $filePath;
    }
});

(new App\Http\Router(new App\Http\ReportController()))->dispatch();
