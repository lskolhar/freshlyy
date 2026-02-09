<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// app/Http/Controllers/OrderController.php

use App\Models\Order;
use App\Models\OrderItem;
class OrderController extends Controller
{
    public function store()
    {
        if (auth()->user()->role !== 'user') {
            abort(403);
        }

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect('/cart')->with('error', 'Cart is empty');
        }

        $total = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        $order = Order::create([
            'user_id' => auth()->id(),
            'total' => $total,
        ]);

        foreach ($cart as $productId => $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $productId,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        session()->forget('cart');

        return redirect('/')->with('success', 'Order placed successfully');
    }
}

