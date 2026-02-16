@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">

    <h1 class="text-2xl font-bold mb-6">
        {{ auth()->user()->role === 'admin' ? 'All Orders' : 'My Orders' }}
    </h1>

    @if($orders->isEmpty())
        <p class="text-gray-600">No orders found.</p>
    @else

        @foreach($orders as $order)

            <div class="bg-white shadow rounded-lg mb-6 p-6">

                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'paid' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                @endphp

                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="font-semibold text-lg">
                            Order # {{ $order->order_number }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            {{ $order->created_at->format('d M Y, h:i A') }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="font-semibold text-lg">
                            ₹{{ $order->total_amount }}
                        </p>

                        {{-- STATUS SECTION --}}
                        @if(auth()->user()->role === 'admin')

                            @if($order->status !== 'cancelled')

                                <form action="{{ route('orders.updateStatus', $order) }}"
                                      method="POST"
                                      class="mt-2"
                                      onsubmit="return confirmStatusChange()">

                                    @csrf
                                    @method('PATCH')

                                    <select name="status"
                                            class="border rounded px-2 py-1 text-sm">

                                        <option value="pending"
                                            {{ $order->status === 'pending' ? 'selected' : '' }}>
                                            Pending
                                        </option>

                                        <option value="paid"
                                            {{ $order->status === 'paid' ? 'selected' : '' }}>
                                            Paid
                                        </option>

                                        <option value="cancelled"
                                            {{ $order->status === 'cancelled' ? 'selected' : '' }}>
                                            Cancelled
                                        </option>

                                    </select>

                                    <button type="submit"
                                            class="ml-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                                        Update
                                    </button>

                                </form>

                            @else
                                <span class="mt-2 inline-block px-3 py-1 rounded-full text-xs font-semibold 
                                    {{ $statusColors['cancelled'] }}">
                                    Cancelled
                                </span>
                            @endif

                        @else
                            <span class="mt-2 inline-block px-3 py-1 rounded-full text-xs font-semibold 
                                {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        @endif

                    </div>
                </div>

                {{-- ADMIN USER INFO --}}
                @if(auth()->user()->role === 'admin')
                    <div class="mb-4 text-sm text-gray-700">
                        <strong>User:</strong> {{ $order->user->name }} <br>
                        <strong>Email:</strong> {{ $order->user->email }}
                    </div>
                @endif

                {{-- ORDER ITEMS --}}
                <div class="border-t pt-4">
                    <h3 class="font-medium mb-2">Items</h3>

                    <table class="w-full text-sm border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2">Product</th>
                                <th class="border px-3 py-2">Price</th>
                                <th class="border px-3 py-2">Qty</th>
                                <th class="border px-3 py-2">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderItems as $item)
                                <tr class="text-center">
                                    <td class="border px-3 py-2">
                                        {{ $item->product_name }}
                                    </td>
                                    <td class="border px-3 py-2">
                                        ₹{{ $item->price }}
                                    </td>
                                    <td class="border px-3 py-2">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="border px-3 py-2">
                                        ₹{{ $item->subtotal }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>

            </div>

        @endforeach

    @endif

</div>

<script>
function confirmStatusChange() {
    return confirm("Are you sure you want to update this order status?");
}
</script>
<script>
function confirmStatusChange(event) {
    return confirm("Are you sure you want to update this order status?");
}
</script>

@endsection
