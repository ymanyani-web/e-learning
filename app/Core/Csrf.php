<?php

declare(strict_types=1);

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf_token');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf_token', $token);
        }

        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(): void
    {
        $submitted = $_POST['_token'] ?? '';
        if (!is_string($submitted) || !hash_equals(self::token(), $submitted)) {
            Response::abort(419, 'Your session has expired. Please reload the page and try again.');
        }
    }
}
