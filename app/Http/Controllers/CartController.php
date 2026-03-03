<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function cartKey()
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        return 'cart_' . auth()->id();
    }

    public function index()
    {
        $cart = session()->get($this->cartKey(), []);
        
        uasort($cart, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return view('cart', compact('cart'));
    }

    public function add(Product $product)
    {
        $key = $this->cartKey();
        $cart = session()->get($key, []);

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] += 1;
        } else {
            $cart[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => 1,
            ];
        }

        session()->put($key, $cart);

        return back()->with('success', 'Product added to cart.');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $key = $this->cartKey();
        $cart = session()->get($key, []);

        if (!isset($cart[$product->id])) {
            return back();
        }

        $quantity = (int) $request->quantity;

        if ($quantity === 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id]['quantity'] = $quantity;
        }

        session()->put($key, $cart);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(Product $product)
    {
        $key = $this->cartKey();
        $cart = session()->get($key, []);

        if (isset($cart[$product->id])) {
            unset($cart[$product->id]);
        }

        session()->put($key, $cart);

        return back()->with('success', 'Product removed.');
    }

    public function clear()
    {
        $key = $this->cartKey();

        // Clear session cart
        session()->forget($key);

        // Cancel latest pending order (if exists)
        $order = \App\Models\Order::where('user_id', auth()->id())
            ->where('status', \App\Models\Order::STATUS_PENDING)
            ->latest()
            ->first();

        if ($order) {
            $order->update([
                'status' => 'cancelled'
            ]);
        }

        return redirect()->route('home')
            ->with('success', 'Cart cleared and order cancelled.');
    }



}
