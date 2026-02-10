{{-- resources/views/components/guest-layout.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Freshlyy') }}</title>

    {{-- Tailwind / Vite --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-900">

    <div class="min-h-screen flex flex-col items-center justify-center">
        <div class="w-full max-w-md bg-white p-6 rounded-lg shadow">

            {{-- Page content comes here --}}
            {{ $slot }}

        </div>
    </div>

</body>

</html>