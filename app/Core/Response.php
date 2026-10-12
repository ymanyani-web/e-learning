<?php

namespace App\Core;

class Response
{
    public static function redirect(string $path): never
    {
        $basePath = Request::basePath();
        $location = $basePath . '/' . ltrim($path, '/');
        header('Location: ' . ($location === '/' ? '/' : $location));
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        echo $message !== '' ? $message : "HTTP {$status}";
        exit;
    }
}
