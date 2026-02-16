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
                'name'       => $product->name,
                'price'      => (float) $product->price,
                'quantity'   => 1,
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
        session()->forget($this->cartKey());
        return back()->with('success', 'Cart cleared.');
    }
}
