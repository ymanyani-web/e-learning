<?php

namespace App\Core;

use App\Repositories\UserRepository;

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $repository = new UserRepository();
        $user = $repository->findByEmail($email);

        if ($user === null || !$user->isActive()) {
            return false;
        }

        if (!password_verify($password, $user->getPassword())) {
            return false;
        }

        session_regenerate_id(true);

        Session::put('user', [
            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
        ]);

        return true;
    }

    public static function check(): bool
    {
        return Session::get('user') !== null;
    }

    public static function user(): ?array
    {
        return Session::get('user');
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Please log in first.');
            Response::redirect('/login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();

        if (!in_array(self::role(), $roles, true)) {
            Response::abort(403, 'Forbidden');
        }
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
