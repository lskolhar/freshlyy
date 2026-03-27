<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Pipeline;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Requests\LoginRequest;

class SessionController extends Controller
{
    public function create()
    {
        // No role handling anymore
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        return (new Pipeline(app()))
            ->send($request)
            ->through(array_filter([
                config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectsIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]))
            ->then(fn () => redirect('/'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
