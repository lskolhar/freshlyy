@extends('layouts.app')

@section('title', 'Register')

@section('heading')
    Register
@endsection

@section('content')
<div class="max-w-md mx-auto mt-10 bg-white p-8 rounded-xl border border-gray-200 shadow-sm">

    <form method="POST" action="/register" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <x-form-label for="name">Full name</x-form-label>
            <div class="mt-2">
                <x-form-input
                    id="name"
                    name="name"
                    type="text"
                    placeholder="Jane Doe"
                    :value="old('name')"
                    required
                />
                <x-form-error name="name" />
            </div>
        </div>

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
                    placeholder="Create a password"
                    required
                />
                <x-form-error name="password" />
            </div>
        </div>

        <!-- Confirm Password -->
        <div>
            <x-form-label for="password_confirmation">Confirm password</x-form-label>
            <div class="mt-2">
                <x-form-input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    placeholder="Re-enter your password"
                    required
                />
                <x-form-error name="password_confirmation" />
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-4">
            <a href="/login"
               class="text-sm font-medium text-gray-600 hover:text-gray-900">
                Already registered?
            </a>

<x-form-button :active="true">
    Register
</x-form-button>

        </div>

    </form>

</div>
@endsection
