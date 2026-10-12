<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirectByRole();
        }

        View::render('auth/login', [
            'error' => Session::consumeFlash('error'),
            'success' => Session::consumeFlash('success'),
        ]);
    }

    public function login(): void
    {
        $email = trim((string) Request::input('email', ''));
        $password = (string) Request::input('password', '');

        if ($email === '' || $password === '') {
            Session::flash('error', 'Email and password are required.');
            Response::redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            Session::flash('error', 'Invalid credentials or inactive account.');
            Response::redirect('/login');
        }

        $this->redirectByRole();
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        Session::flash('success', 'You are now logged out.');
        Response::redirect('/login');
    }

    private function redirectByRole(): never
    {
        match (Auth::role()) {
            'ADMIN' => Response::redirect('/admin'),
            'TEACHER' => Response::redirect('/teacher'),
            'STUDENT' => Response::redirect('/student'),
            default => Response::abort(403, 'Unknown role'),
        };
    }
}
