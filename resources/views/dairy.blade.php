@extends('layouts.app')

@section('heading')
    🥛 Dairy Products
@endsection

@section('content')

{{-- Intro text --}}
<p class="text-gray-600 mb-6">
    Fresh milk, cheese, butter and more — sourced daily for best quality.
</p>

{{-- Products Grid --}}
@if($category->products->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-md">
        No dairy products available right now.
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

        @foreach($category->products as $product)
            <div class="border rounded-lg p-4 shadow-sm hover:shadow-md transition">

                {{-- Product image placeholder --}}
                <div class="h-32 bg-gray-100 rounded mb-4 flex items-center justify-center text-4xl">
                    🥛
                </div>

                {{-- Product name --}}
                <h3 class="text-lg font-semibold text-gray-800">
                    {{ $product->name }}
                </h3>

                {{-- Price --}}
                <p class="text-green-600 font-medium mt-1 mb-3">
                    ₹{{ $product->price }}
                </p>

                {{-- Add to cart --}}
                <form method="POST" action="{{ route('cart.add', $product->id) }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition">
                        Add to Cart
                    </button>
                </form>

            </div>
        @endforeach

    </div>
@endif

@endsection
