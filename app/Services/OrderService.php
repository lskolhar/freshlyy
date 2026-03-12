<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

class OrderService
{
    public function getUserCart($userId)
    {
        return session()->get('cart_' . $userId, []);
    }

    public function clearUserCart($userId)
    {
        session()->forget('cart_' . $userId);
    }

    public function createOrderFromCart($userId, $cart)
    {
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'user_id' => $userId,
            'total_amount' => $total,
            'status' => Order::STATUS_PENDING,
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

        return $order;
    }

    public function getUserOrder($orderNumber, $userId)
    {
        return Order::where('order_number', $orderNumber)
            ->where('user_id', $userId)
            ->first();
    }
}