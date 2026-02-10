<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function create(Request $request)
    {
        return view('auth.login', [
            'role' => $request->query('role', 'user')
        ]);
    }


    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Invalid credentials',
                'password' => 'Invalid password',
            ]);
        }

        $request->session()->regenerate();

        // 🔐 ROLE CHECK
        if ($request->role !== auth()->user()->role) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Unauthorized login attempt',
            ]);
        }

        // Redirect based on role
        if (auth()->user()->role === 'admin') {
            return redirect()->route('home');
        }

        return redirect('/');
    }


    public function destroy()
    {

        Auth::logout();

        return redirect('/');

    }
}
