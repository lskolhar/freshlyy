@extends('layouts.app')

@section('title', 'Login')

@section('heading')
    Log In
@endsection

@section('content')
<div class="max-w-md mx-auto mt-10 bg-white p-8 rounded-xl border border-gray-200 shadow-sm">

    <form method="POST" action="/login" class="space-y-6">
        @csrf
            <input type="hidden" name="role" value="{{ $role ?? request('role', 'user') }}">

        <!-- Email -->
        <div>
            <x-form-label for="email">Email address</x-form-label>
            <div class="mt-2">
                <x-form-input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="janedoe@example.com"
                    :value="old('email')"
                    required
                />
                <x-form-error name="email" />
            </div>
        </div>

        <!-- Password -->
        <div>
            <x-form-label for="password">Password</x-form-label>
            <div class="mt-2">
                <x-form-input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Enter your password"
                    required
                />
                <x-form-error name="password" />
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-4">
            <a href="/register"
               class="text-sm font-medium text-gray-600 hover:text-gray-900">
                Don’t have an account?
            </a>

<x-form-button :active="true">
    Log In
</x-form-button>

        </div>
    </form>

</div>
@endsection
