<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\User;
use App\Repositories\UserRepository;

class UserController
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function index(): void
    {
        Auth::requireRole('ADMIN');

        View::render('admin/users/index', [
            'users' => $this->users->findAll(),
            'success' => Session::consumeFlash('success'),
            'error' => Session::consumeFlash('error'),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('ADMIN');

        View::render('admin/users/create', [
            'error' => Session::consumeFlash('error'),
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('ADMIN');
        Csrf::validate();

        $firstName = trim((string) Request::input('first_name', ''));
        $lastName = trim((string) Request::input('last_name', ''));
        $email = strtolower(trim((string) Request::input('email', '')));
        $password = (string) Request::input('password', '');
        $role = strtoupper(trim((string) Request::input('role', 'STUDENT')));

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            Session::flash('error', 'All fields are required.');
            Response::redirect('/admin/users/create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid email address.');
            Response::redirect('/admin/users/create');
        }

        if (!in_array($role, ['ADMIN', 'TEACHER', 'STUDENT'], true)) {
            Session::flash('error', 'Invalid role.');
            Response::redirect('/admin/users/create');
        }

        if (strlen($password) < 8) {
            Session::flash('error', 'Password must contain at least 8 characters.');
            Response::redirect('/admin/users/create');
        }

        if ($this->users->findByEmail($email) !== null) {
            Session::flash('error', 'Email already exists.');
            Response::redirect('/admin/users/create');
        }

        $user = new User(
            null,
            $firstName,
            $lastName,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            true
        );

        $this->users->create($user);

        Session::flash('success', 'User created successfully.');
        Response::redirect('/admin/users');
    }
}
