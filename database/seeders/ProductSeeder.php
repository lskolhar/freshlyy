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
            ['category_id' => $dairy->id, 'name' => 'Milk', 'price' => 50],
            ['category_id' => $dairy->id, 'name' => 'Toned Milk', 'price' => 55],
            ['category_id' => $dairy->id, 'name' => 'Curd', 'price' => 45],
            ['category_id' => $dairy->id, 'name' => 'Butter', 'price' => 60],
            ['category_id' => $dairy->id, 'name' => 'Cheese', 'price' => 120],
            ['category_id' => $dairy->id, 'name' => 'Paneer', 'price' => 180],
            ['category_id' => $dairy->id, 'name' => 'Ghee', 'price' => 550],

            // 🥦 VEGETABLES
            ['category_id' => $vegetables->id, 'name' => 'Tomato', 'price' => 30],
            ['category_id' => $vegetables->id, 'name' => 'Potato', 'price' => 40],
            ['category_id' => $vegetables->id, 'name' => 'Onion', 'price' => 35],
            ['category_id' => $vegetables->id, 'name' => 'Carrot', 'price' => 50],
            ['category_id' => $vegetables->id, 'name' => 'Cabbage', 'price' => 45],
            ['category_id' => $vegetables->id, 'name' => 'Cauliflower', 'price' => 60],
            ['category_id' => $vegetables->id, 'name' => 'Spinach', 'price' => 25],

            // 🍎 FRUITS
            ['category_id' => $fruits->id, 'name' => 'Apple', 'price' => 120],
            ['category_id' => $fruits->id, 'name' => 'Banana', 'price' => 60],
            ['category_id' => $fruits->id, 'name' => 'Orange', 'price' => 80],
            ['category_id' => $fruits->id, 'name' => 'Mango', 'price' => 100],
            ['category_id' => $fruits->id, 'name' => 'Grapes', 'price' => 90],
            ['category_id' => $fruits->id, 'name' => 'Papaya', 'price' => 70],

            // 🍗 MEAT
            ['category_id' => $meat->id, 'name' => 'Chicken', 'price' => 150],
            ['category_id' => $meat->id, 'name' => 'Chicken Breast', 'price' => 220],
            ['category_id' => $meat->id, 'name' => 'Chicken Curry Cut', 'price' => 180],
            ['category_id' => $meat->id, 'name' => 'Mutton', 'price' => 200],
            ['category_id' => $meat->id, 'name' => 'Mutton Curry Cut', 'price' => 280],
            ['category_id' => $meat->id, 'name' => 'Fish', 'price' => 160],
        ]);

    }
}
