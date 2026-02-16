@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto px-6 py-6">

        <h1 class="text-3xl font-bold mb-6">🛒 Your Cart</h1>

        @if(empty($cart))
            <p class="text-gray-500">Your cart is empty.</p>
        @else
            <div class="bg-white rounded-lg shadow p-6">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $grandTotal = 0; @endphp

                        @foreach($cart as $id => $item)
                            @php
                                $total = $item['price'] * $item['quantity'];
                                $grandTotal += $total;
                            @endphp
                            <tr class="border-b">
                                <td class="py-2">{{ $item['name'] }}</td>
                                <td class="text-center">₹{{ $item['price'] }}</td>

                                <td class="text-center">
                                    <form method="POST" action="{{ route('cart.update', $id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1"
                                            class="w-16 border rounded text-center">
                                    </form>
                                </td>

                                <td class="text-center">₹{{ $total }}</td>

                                <td class="text-center">
                                    <form method="POST" action="{{ route('cart.remove', $id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="text-right mt-6">
                    <p class="text-xl font-semibold">
                        Total: ₹{{ $grandTotal }}
                    </p>

                    <button class="mt-4 bg-freshly text-white px-6 py-2 rounded hover:bg-freshly-dark">
                        Proceed to Checkout
                    </button>
                </div>
            </div>
        @endif
        @if(count($cart) > 0)
            <div class="mt-6 flex justify-end">
                <form action="{{ route('checkout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="bg-green-400 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-lg shadow-md transition duration-200">
                        Pay
                    </button>
                    <button type="submit"
                        class="bg-red-400 hover:bg-red-600 text-white font-semibold px-6 py-3 rounded-lg shadow-md transition duration-200">
                        Cancel
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection