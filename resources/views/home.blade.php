@extends('layouts.app')

@section('title', 'Freshly | Fresh Groceries Delivered')

@section('content')

    <!-- HERO SECTION -->
    <section class="bg-green-50 rounded-xl p-6 mb-12 flex flex-col md:flex-row items-center justify-between">
        <div class="max-w-xl">
            <h1 class="text-4xl font-bold text-gray-800 mb-6">
                Fresh groceries, delivered daily.
            </h1>
            <p class="text-gray-600 mb-4">
                Experience the goodness of farm-fresh dairy, vegetables, fruits, and meat, thoughtfully sourced from local
                farms and delivered fresh to your doorstep every single day. </p>

        </div>

        <img src="https://cdn-icons-png.flaticon.com/512/706/706164.png" alt="Groceries" class="w-64 mt-6 md:mt-0">
    </section>

    <!-- CATEGORY SECTION -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">
            Shop by Category
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <a href="/category/dairy" class="border rounded-lg p-6 text-center hover:shadow-md transition">
                🥛
                <h3 class="mt-2 font-semibold">Dairy</h3>
            </a>

            <a href="/category/vegetables" class="border rounded-lg p-6 text-center hover:shadow-md transition">
                🥦
                <h3 class="mt-2 font-semibold">Vegetables</h3>
            </a>

            <a href="/category/fruits" class="border rounded-lg p-6 text-center hover:shadow-md transition">
                🍎
                <h3 class="mt-2 font-semibold">Fruits</h3>
            </a>

            <a href="/category/meat" class="border rounded-lg p-6 text-center hover:shadow-md transition">
                🍗
                <h3 class="mt-2 font-semibold">Meat</h3>
            </a>
        </div>
    </section>

    <!-- WHY CHOOSE US -->
    <section class="bg-gray-50 rounded-xl p-8 mb-12">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">
            Why Freshly?
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-center">
            <div>
                🚚
                <p class="mt-2 font-medium">Fast Delivery</p>
            </div>

            <div>
                🥬
                <p class="mt-2 font-medium">Farm Fresh</p>
            </div>

            <div>
                💳
                <p class="mt-2 font-medium">Secure Payments</p>
            </div>

            <div>
                📦
                <p class="mt-2 font-medium">Easy Returns</p>
            </div>
        </div>
    </section>

    <!-- CALL TO ACTION -->
    <section class="text-center mb-16">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">
            Start your freshly journey today !
        </h2>

    </section>

@endsection