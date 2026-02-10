{{-- resources/views/auth/login-options.blade.php --}}
<x-guest-layout>
    <h2 class="text-xl font-bold mb-4">Login as</h2>

    <div class="space-y-3">
        <input type="hidden" name="role" value="{{ request('role', 'user') }}">

        <a href="{{ route('login', ['role' => 'user']) }}"
            class="block bg-green-600 text-white text-center py-2 rounded">
            User Login
        </a>

        <a href="{{ route('login', ['role' => 'admin']) }}"
            class="block bg-blue-600 text-white text-center py-2 rounded">
            Admin Login
        </a>
    </div>
</x-guest-layout>