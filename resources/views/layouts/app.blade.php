<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Freshly')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white">

    <header class="w-full border-b border-gray-200">
        <nav class="w-full px-8">
            <div class="flex h-16 items-center justify-between">

                <!-- Logo -->
                <a href="/" class="flex items-center space-x-2">
                    <img src="https://th.bing.com/th/id/R.8f83826aeb44d8db5c9028926ed9b2f1?rik=M35n2CkAvXWueA&riu=http%3a%2f%2ffreshlyproduct.com%2fassets%2fimg%2flogo-og.jpg&ehk=OA7H%2b6PAUDaW%2f6fmQGFpl1lF9faZfd1xoo2%2bU19Jevw%3d&risl=&pid=ImgRaw&r=0"
                        class="h-12 w-auto" alt="Freshly">
                    <span class="text-lg font-semibold text-gray-800">Freshly</span>
                </a>

                <!-- Navigation -->
                <div class="flex space-x-4">
                    <x-button href="/category/dairy" :active="request()->is('category/dairy')">Dairy</x-button>
                    <x-button href="/category/vegetables"
                        :active="request()->is('category/vegetables')">Vegetables</x-button>
                    <x-button href="/category/fruits" :active="request()->is('category/fruits')">Fruits</x-button>
                    <x-button href="/category/meat" :active="request()->is('category/meat')">Meat</x-button>

                </div>


                <!-- Right side -->
                <div class="flex items-center space-x-4">

                  

                    @guest
                        <x-nav-link href="/login-options" variant="outline" :active="request()->is('login')">
                            Log In
                        </x-nav-link>

                        <x-nav-link href="/register-options" variant="outline" :active="request()->is('register')">
                            Register
                        </x-nav-link>
                    @endguest

                    @auth
                        <form method="POST" action="/logout">
                            @csrf
                            <x-form-button :active="true">
                                Log Out
                            </x-form-button>

                        </form>
                    @endauth
                    


                    <!-- Cart -->
                    <x-form-button href="/cart">
                        Cart ({{ count(session('cart', [])) }})
                    </x-form-button>
                </div>

            </div>
        </nav>
    </header>

    <!-- Page Heading -->
    <h1 class="px-8 py-6 text-2xl font-semibold text-gray-800">
        @yield('heading')
    </h1>

    <!-- Page Content -->
    <main class="px-8">
        @yield('content')
    </main>

</body>

</html>