<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterUserController extends Controller
{
    use PasswordValidationRules;

    public function create()
    {
        // No role from URL anymore
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => $this->passwordRules(),
        ]);

        $attributes['password'] = bcrypt($attributes['password']);

        // 🔒 Force role to user
        $attributes['role'] = 'user';

        $user = User::create($attributes);

        Auth::login($user);

        return redirect('/');
    }
}
