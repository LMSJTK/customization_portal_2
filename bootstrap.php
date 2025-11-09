<?php

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

spl_autoload_register(function (string $class): void {
    $prefix = 'CustomizationPortal\\';
    if (str_starts_with($class, $prefix)) {
        $path = __DIR__ . '/src/' . str_replace('CustomizationPortal\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

function app_config(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/config/app.php';
    }

    return $config;
}

function start_session_if_needed(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $config = app_config();
        session_set_cookie_params([
            'lifetime' => $config['session_cookie_lifetime'] ?? 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function app_base_path(): string
{
    $config = app_config();
    $basePath = trim($config['app_base_path'] ?? '/');

    if ($basePath === '') {
        $basePath = '/';
    }

    if ($basePath[0] !== '/') {
        $basePath = '/' . $basePath;
    }

    $basePath = rtrim($basePath, '/');

    return $basePath === '' ? '/' : $basePath;
}

function app_url(string $path = '/'): string
{
    $base = app_base_path();
    $normalizedPath = $path === '' ? '/' : ('/' . ltrim($path, '/'));

    if ($normalizedPath === '/' || $normalizedPath === '') {
        return $base === '/' ? '/' : $base . '/';
    }

    return ($base === '/' ? '' : $base) . $normalizedPath;
}
