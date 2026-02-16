<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private function cartKey()
    {
        return 'cart_' . auth()->id();
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->back()->with('error', 'Please login first.');
        }

        $cart = session()->get($this->cartKey(), []);

        if (empty($cart)) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        DB::transaction(function () use ($cart, $user) {

            $total = 0;

            foreach ($cart as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'user_id' => $user->id,
                'total_amount' => $total,
                'status' => 'paid', // later change to pending for payment gateway
            ]);

            foreach ($cart as $productId => $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }


            session()->forget($this->cartKey());
        });

        return redirect('/')->with('success', 'Order placed successfully.');
    }
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $orders = Order::with(['user', 'orderItems'])
                ->latest()
                ->get();
        } else {
            $orders = Order::with('orderItems')
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        }

        return view('orders', compact('orders'));
    }
    public function updateStatus(Request $request, Order $order)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        if ($order->status === 'cancelled') {
            return redirect()->back()->with('error', 'Cancelled orders cannot be modified.');
        }

        $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        $order->update([
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Order status updated.');
    }



}
