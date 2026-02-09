<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // database/seeders/CategorySeeder.php
        Category::insert([
            ['name' => 'Dairy', 'slug' => 'dairy'],
            ['name' => 'Vegetables', 'slug' => 'vegetables'],
            ['name' => 'Fruits', 'slug' => 'fruits'],
            ['name' => 'Meat', 'slug' => 'meat'],
        ]);



    }
}
