<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class DashboardController
{
    public function admin(): void
    {
        Auth::requireRole('ADMIN');

        View::render('dashboard/admin', [
            'user' => Auth::user(),
        ]);
    }

    public function teacher(): void
    {
        Auth::requireRole('TEACHER');

        View::render('dashboard/teacher', [
            'user' => Auth::user(),
        ]);
    }

    public function student(): void
    {
        Auth::requireRole('STUDENT');

        View::render('dashboard/student', [
            'user' => Auth::user(),
        ]);
    }
}
