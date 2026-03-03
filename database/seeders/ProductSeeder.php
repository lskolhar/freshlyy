<?php

namespace Database\Seeders;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $dairy = Category::where('slug', 'dairy')->firstOrFail();
        $vegetables = Category::where('slug', 'vegetables')->firstOrFail();
        $fruits = Category::where('slug', 'fruits')->firstOrFail();
        $meat = Category::where('slug', 'meat')->firstOrFail();


        Product::insert([
            // 🥛 DAIRY
            ['category_id' => $dairy->id, 'name' => 'Milk', 'quantity' => '1 L', 'price' => 50],
            ['category_id' => $dairy->id, 'name' => 'Toned Milk', 'quantity' => '1 L', 'price' => 55],
            ['category_id' => $dairy->id, 'name' => 'Curd', 'quantity' => '500 g', 'price' => 45],
            ['category_id' => $dairy->id, 'name' => 'Butter', 'quantity' => '100 g', 'price' => 60],
            ['category_id' => $dairy->id, 'name' => 'Cheese', 'quantity' => '200 g', 'price' => 120],
            ['category_id' => $dairy->id, 'name' => 'Paneer', 'quantity' => '200 g', 'price' => 180],
            ['category_id' => $dairy->id, 'name' => 'Ghee', 'quantity' => '500 ml', 'price' => 550],

            // 🥦 VEGETABLES
            ['category_id' => $vegetables->id, 'name' => 'Tomato', 'quantity' => '1 kg', 'price' => 30],
            ['category_id' => $vegetables->id, 'name' => 'Potato', 'quantity' => '1 kg', 'price' => 40],
            ['category_id' => $vegetables->id, 'name' => 'Onion', 'quantity' => '1 kg', 'price' => 35],
            ['category_id' => $vegetables->id, 'name' => 'Carrot', 'quantity' => '500 g', 'price' => 50],
            ['category_id' => $vegetables->id, 'name' => 'Cabbage', 'quantity' => '1 pc', 'price' => 45],
            ['category_id' => $vegetables->id, 'name' => 'Cauliflower', 'quantity' => '1 pc', 'price' => 60],
            ['category_id' => $vegetables->id, 'name' => 'Spinach', 'quantity' => '1 bunch', 'price' => 25],

            // 🍎 FRUITS
            ['category_id' => $fruits->id, 'name' => 'Apple', 'quantity' => '1 kg', 'price' => 120],
            ['category_id' => $fruits->id, 'name' => 'Banana', 'quantity' => '1 dozen', 'price' => 60],
            ['category_id' => $fruits->id, 'name' => 'Orange', 'quantity' => '1 kg', 'price' => 80],
            ['category_id' => $fruits->id, 'name' => 'Mango', 'quantity' => '1 kg', 'price' => 100],
            ['category_id' => $fruits->id, 'name' => 'Grapes', 'quantity' => '500 g', 'price' => 90],
            ['category_id' => $fruits->id, 'name' => 'Papaya', 'quantity' => '1 pc', 'price' => 70],

            // 🍗 MEAT
            ['category_id' => $meat->id, 'name' => 'Chicken', 'quantity' => '1 kg', 'price' => 150],
            ['category_id' => $meat->id, 'name' => 'Chicken Breast', 'quantity' => '500 g', 'price' => 220],
            ['category_id' => $meat->id, 'name' => 'Chicken Curry Cut', 'quantity' => '500 g', 'price' => 180],
            ['category_id' => $meat->id, 'name' => 'Mutton', 'quantity' => '500 g', 'price' => 200],
            ['category_id' => $meat->id, 'name' => 'Mutton Curry Cut', 'quantity' => '500 g', 'price' => 280],
            ['category_id' => $meat->id, 'name' => 'Fish', 'quantity' => '1 kg', 'price' => 160],
        ]);

    }
}
