<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthPageController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function register(): Response
    {
        return response()->view('auth.register', status: 403);
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function resetPassword(Request $request): View
    {
        return view('auth.reset-password', [
            'token' => (string) $request->query('token', $request->query('t', '')),
        ]);
    }

    public function logout(): View
    {
        return view('auth.logout');
    }
}
