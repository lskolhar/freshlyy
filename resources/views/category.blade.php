@extends('layouts.app')

@php
    $theme = match($category->slug) {
        'dairy' => [
            'bg' => 'bg-blue-50',
            'card' => 'border-blue-200',
            'button' => 'bg-blue-400 hover:bg-blue-700',
            'emoji' => '🥛',
        ],
        'vegetables' => [
            'bg' => 'bg-green-50',
            'card' => 'border-green-200',
            'button' => 'bg-green-400 hover:bg-green-700',
            'emoji' => '🥦',
        ],
        'fruits' => [
            'bg' => 'bg-pink-50',
            'card' => 'border-pink-200',
            'button' => 'bg-pink-400 hover:bg-pink-700',
            'emoji' => '🍎',
        ],
        'meat' => [
            'bg' => 'bg-red-50',
            'card' => 'border-red-200',
            'button' => 'bg-red-500 hover:bg-red-700',
            'emoji' => '🍗',
        ],
        default => [
            'bg' => 'bg-gray-50',
            'card' => 'border-gray-200',
            'button' => 'bg-gray-400 hover:bg-gray-700',
            'emoji' => '🛒',
        ],
    };
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8 {{ $theme['bg'] }} rounded-xl">

    <!-- Category Title -->
    <h1 class="text-3xl font-bold mb-2 flex items-center gap-2">
        <span>{{ $theme['emoji'] }}</span>
        {{ $category->name }}
    </h1>

    <p class="text-gray-600 mb-6">
        Fresh {{ strtolower($category->name) }} delivered daily.
    </p>

    <!-- If no products -->
    @if($category->products->isEmpty())
        <p class="text-gray-500">No products available in this category.</p>
    @else
        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach ($category->products as $product)
                <div class="border rounded-lg p-4 shadow-sm hover:shadow-md transition {{ $theme['card'] }} bg-white">



                    <!-- Product Name -->
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ $product->name }}
                    </h3>

                    <!-- Price -->
                    <p class="text-gray-700 mb-3">
                        ₹{{ number_format($product->price, 2) }}
                    </p>

                    <!-- Add to Cart -->
                    <form method="POST" action="/cart">
                        @csrf
                        <button
                            type="submit"
                            class="w-full text-white py-2 rounded transition {{ $theme['button'] }}">
                            Add to Cart
                        </button>
                    </form>

                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
