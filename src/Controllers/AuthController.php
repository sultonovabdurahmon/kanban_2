<?php
declare(strict_types=1);

namespace App\Controllers;

final class AuthController extends Controller
{
    /** GET /login — шакл бо макети алоҳида (коркарди POST дар кори лаб. 14 пайдо мешавад) */
    public function showLogin(): void
    {
        $this->render('auth/login', ['title' => 'Воридшавӣ'], 200, 'auth/layout');
    }
}
