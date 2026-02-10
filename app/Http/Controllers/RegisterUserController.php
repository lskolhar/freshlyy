<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterUserController extends Controller
{
    public function create(Request $request)
    {
        // role comes from URL: ?role=admin or ?role=user
        return view('auth.register', [
            'role' => $request->query('role', 'user')
        ]);
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:4', 'confirmed'],
        ]);

        $attributes['password'] = bcrypt($attributes['password']);

        // 👇 THIS is the key line
        $attributes['role'] = $request->input('role', 'user');

        $user = User::create($attributes);

        Auth::login($user);

        // Redirect based on role
        if ($user->role === 'admin') {
            return redirect()->route('home');
        }

        return redirect('/');
    }
}
