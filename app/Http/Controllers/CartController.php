<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function cartKey()
    {
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
            $cart[$product->id]['quantity']++;
        } else {
            $cart[$product->id] = [
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
            ];
        }

        session()->put($key, $cart);
        return back();
    }

    public function update(Request $request, Product $product)
    {
        $key = $this->cartKey();
        $cart = session()->get($key, []);

        if (!isset($cart[$product->id])) {
            return back();
        }

        $quantity = (int) $request->quantity;

        if ($quantity <= 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id]['quantity'] = $quantity;
        }

        session()->put($key, $cart);
        return back();
    }

    public function remove(Product $product)
    {
        $key = $this->cartKey();
        $cart = session()->get($key, []);

        if (isset($cart[$product->id])) {
            unset($cart[$product->id]);
        }

        session()->put($key, $cart);
        return back();
    }
}
