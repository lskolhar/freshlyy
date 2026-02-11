<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function create()
    {
        // No role handling anymore
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Invalid credentials.',
            ]);
        }

        $request->session()->regenerate();

        // No role check here
        // Role only affects UI + middleware

        return redirect('/');
    }

    public function destroy()
    {
        Auth::logout();
        return redirect('/');
    }
}
