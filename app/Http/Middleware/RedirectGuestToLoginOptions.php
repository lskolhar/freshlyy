<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectGuestToLoginOptions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // 👇 THIS IS WHERE IT GOES
        if (! auth()->check()) {
            return redirect()->route('login.options');
        }

        return $next($request);
    }
}
