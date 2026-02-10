<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show($slug)
    {
        $category = Category::where('slug', $slug)
            ->with(['products' => fn($q) => $q->orderBy('name')])
            ->firstOrFail();

        $cart = session()->get('cart', []);

        return view('category', compact('category', 'cart'));
    }

}
