@extends('layouts.app')

@php
    $theme = match ($category->slug) {
        'dairy' => [
            'bg' => 'bg-blue-50',
            'card' => 'border-blue-200',
            'button' => 'bg-blue-500 hover:bg-blue-700',
            'text' => 'text-blue-600',
            'emoji' => '🥛',
        ],
        'vegetables' => [
            'bg' => 'bg-green-50',
            'card' => 'border-green-200',
            'button' => 'bg-green-500 hover:bg-green-700',
            'text' => 'text-green-600',
            'emoji' => '🥦',
        ],
        'fruits' => [
            'bg' => 'bg-pink-50',
            'card' => 'border-pink-200',
            'button' => 'bg-pink-500 hover:bg-pink-700',
            'text' => 'text-pink-600',
            'emoji' => '🍎',
        ],
        'meat' => [
            'bg' => 'bg-red-50',
            'card' => 'border-red-200',
            'button' => 'bg-red-500 hover:bg-red-700',
            'text' => 'text-red-600',
            'emoji' => '🍗',
        ],
        default => [
            'bg' => 'bg-gray-50',
            'card' => 'border-gray-200',
            'button' => 'bg-gray-500 hover:bg-gray-700',
            'text' => 'text-gray-600',
            'emoji' => '🛒',
        ],
    };
@endphp

@section('content')
    <div class="max-w-7xl mx-auto px-6 py-8 {{ $theme['bg'] }} rounded-xl">

        {{-- Category Header --}}
        <h1 class="text-3xl font-bold mb-2 flex items-center gap-2">
            <span>{{ $theme['emoji'] }}</span>
            {{ $category->name }}
        </h1>

        <p class="text-gray-600 mb-6">
            Fresh {{ strtolower($category->name) }} delivered daily.
        </p>

        {{-- Products Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

            {{-- ADMIN: Add Product Card --}}
            @if(auth()->check() && auth()->user()->role === 'admin')
                <div onclick="openAddModal()" class="border-2 border-dashed rounded-lg p-4 flex flex-col items-center justify-center
                                       cursor-pointer transition hover:shadow-md {{ $theme['card'] }} bg-white">

                    <div class="text-4xl mb-2 {{ $theme['text'] }}">➕</div>
                    <p class="font-semibold text-gray-700">Add Product</p>
                </div>
            @endif

            {{-- Product Cards --}}
            @foreach ($category->products as $product)
                <div class="border rounded-lg p-4 shadow-sm hover:shadow-md transition {{ $theme['card'] }} bg-white relative">

                    {{-- ADMIN CONTROLS --}}
                    @if(auth()->check() && auth()->user()->role === 'admin')
                        <div class="absolute top-2 left-2 flex gap-2">
                            <button onclick="openEditModal({{ $product->id }}, '{{ $product->name }}', {{ $product->price }})"
                                class="text-xs px-2 py-1 rounded bg-white shadow {{ $theme['text'] }}">
                                Edit ✏️
                            </button>

                            <form method="POST" action="{{ route('products.destroy', $product) }}"
                                onsubmit="return confirm('Delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs px-2 py-1 rounded bg-white shadow text-red-600">
                                    Delete 🗑
                                </button>
                            </form>
                        </div>
                    @endif

                    <h3 class="text-lg font-semibold text-gray-800 mt-6">
                        {{ $product->name }}
                    </h3>

                    <p class="text-gray-700 mb-3">
                        ₹{{ number_format($product->price, 2) }}
                    </p>

                    {{-- USER: Add to Cart --}}
                    @if(!auth()->check() || auth()->user()->role === 'user')
                        <form method="POST" action="{{ route('cart.add', $product) }}">
                            @csrf
                            <button type="submit" class="w-full text-white py-2 rounded transition {{ $theme['button'] }}">
                                Add to Cart
                            </button>
                        </form>

                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ADD PRODUCT MODAL --}}
    @if(auth()->check() && auth()->user()->role === 'admin')
        <div id="addProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg w-96">
                <h2 class="text-lg font-bold mb-4">Add Product</h2>

                <form method="POST" action="{{ route('products.store') }}">
                    @csrf
                    <input type="hidden" name="category_id" value="{{ $category->id }}">

                    <input type="text" name="name" placeholder="Product name" class="w-full border p-2 mb-3 rounded" required>

                    <input type="number" name="price" placeholder="Price" class="w-full border p-2 mb-4 rounded" required>

                    <div class="flex justify-between">
                        <button class="text-white px-4 py-2 rounded {{ $theme['button'] }}">
                            Save
                        </button>
                        <button type="button" onclick="closeAddModal()" class="text-gray-500">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- EDIT PRODUCT MODAL --}}
    @if(auth()->check() && auth()->user()->role === 'admin')
        <div id="editProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg w-96">
                <h2 class="text-lg font-bold mb-4">Edit Product</h2>

                <form method="POST" id="editProductForm">
                    @csrf
                    @method('PUT')

                    <input type="text" id="editName" name="name" class="w-full border p-2 mb-3 rounded" required>

                    <input type="number" id="editPrice" name="price" class="w-full border p-2 mb-4 rounded" required>

                    <div class="flex justify-between">
                        <button class="text-white px-4 py-2 rounded {{ $theme['button'] }}">
                            Update
                        </button>
                        <button type="button" onclick="closeEditModal()" class="text-gray-500">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- JS --}}
    <script>
        function openAddModal() {
            document.getElementById('addProductModal').classList.remove('hidden');
        }
        function closeAddModal() {
            document.getElementById('addProductModal').classList.add('hidden');
        }

        function openEditModal(id, name, price) {
            document.getElementById('editProductForm').action = `/products/${id}`;
            document.getElementById('editName').value = name;
            document.getElementById('editPrice').value = price;
            document.getElementById('editProductModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editProductModal').classList.add('hidden');
        }
    </script>
@endsection