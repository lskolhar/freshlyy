{{-- resources/views/auth/register-options.blade.php --}}
<x-guest-layout>
    <h2 class="text-xl font-bold mb-4">Register as</h2>

    <div class="space-y-3">
        <a href="{{ route('register', ['role' => 'user']) }}"
            class="block bg-green-600 text-white text-center py-2 rounded">
            Register as User
        </a>

        <a href="{{ route('register', ['role' => 'admin']) }}"
            class="block bg-blue-600 text-white text-center py-2 rounded">
            Register as Admin
        </a>
    </div>
</x-guest-layout>