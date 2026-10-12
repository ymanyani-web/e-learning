<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Locale;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use DateTimeImmutable;
use PDO;

class SettingsController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function show(): void
    {
        Auth::requireAuth();
        $this->ensureProfileColumns();
        $profile = $this->profile();
        View::render('portal/settings', [
            'user' => Auth::user(),
            'profile' => $profile,
            'message' => Session::consumeFlash('success'),
            'error' => Session::consumeFlash('error'),
        ]);
    }

    public function update(): void
    {
        Auth::requireAuth();
        Csrf::validate();
        $this->ensureProfileColumns();

        $language = (string) Request::input('preferred_language', 'en');
        $phone = trim((string) Request::input('phone', ''));
        $personalEmail = strtolower(trim((string) Request::input('personal_email', '')));
        $birthDate = trim((string) Request::input('birth_date', ''));
        $password = (string) Request::input('password', '');
        $passwordConfirmation = (string) Request::input('password_confirmation', '');

        if (!in_array($language, ['en', 'fr'], true)) {
            $this->fail('Choose English or French.');
        }
        if ($personalEmail !== '' && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Enter a valid personal email address.');
        }
        if ($birthDate !== '' && DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate)?->format('Y-m-d') !== $birthDate) {
            $this->fail('Enter a valid birth date.');
        }
        if ($password !== '') {
            if (strlen($password) < 8) {
                $this->fail('Password must contain at least 8 characters.');
            }
            if (!hash_equals($password, $passwordConfirmation)) {
                $this->fail('Password confirmation does not match.');
            }
        }

        $sql = 'UPDATE users SET phone = ?, personal_email = ?, birth_date = ?, preferred_language = ?';
        $parameters = [$phone ?: null, $personalEmail ?: null, $birthDate ?: null, $language];
        if ($password !== '') {
            $sql .= ', password = ?';
            $parameters[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ', updated_at = NOW() WHERE id = ?';
        $parameters[] = Auth::id();
        $this->pdo->prepare($sql)->execute($parameters);

        Locale::setLanguage($language);
        $user = Auth::user() ?? [];
        $user['preferred_language'] = $language;
        Session::put('user', $user);
        Session::flash('success', 'Settings saved.');
        Response::redirect('/settings');
    }

    private function profile(): array
    {
        $statement = $this->pdo->prepare('SELECT first_name, last_name, email, phone, personal_email, birth_date, preferred_language FROM users WHERE id = ?');
        $statement->execute([Auth::id()]);
        return $statement->fetch() ?: [];
    }

    private function ensureProfileColumns(): void
    {
        foreach (['phone', 'personal_email', 'birth_date', 'preferred_language'] as $column) {
            $statement = $this->pdo->query("SHOW COLUMNS FROM users LIKE '{$column}'");
            if ($statement->fetch() === false) {
                Response::abort(503, 'Profile settings require the database migration at app/Core/migrations/20261012_add_user_profile.sql.');
            }
        }
    }

    private function fail(string $message): never
    {
        Session::flash('error', $message);
        Response::redirect('/settings');
    }
}
