<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create product', function () {
    $admin = User::factory()->create([
        'role' => 'admin'
    ]);

    $category = Category::factory()->create([
        'slug' => 'dairy'
    ]);

    $this->actingAs($admin);

    $response = $this->post('/products', [
        'name' => 'Milk',
        'price' => 50,
        'quantity' => '10',
        'category_id' => $category->id,
    ]);

    $response->assertStatus(302); // redirect after success

    $this->assertDatabaseHas('products', [
        'name' => 'Milk',
        'category_id' => $category->id,
    ]);
});


test('normal user cannot create product', function () {
    $user = User::factory()->create([
        'role' => 'user'
    ]);

    $category = Category::factory()->create();

    $this->actingAs($user);

    $response = $this->post('/products', [
        'name' => 'Milk',
        'price' => 50,
        'quantity' => '10',
        'category_id' => $category->id,
    ]);

    $response->assertStatus(403); // forbidden

    $this->assertDatabaseMissing('products', [
        'name' => 'Milk'
    ]);
});


test('guest cannot create product', function () {
    $category = Category::factory()->create();

    $response = $this->post('/products', [
        'name' => 'Milk',
        'price' => 50,
        'quantity' => '10',
        'category_id' => $category->id,
    ]);

    $response->assertRedirect('/login'); // guest redirected

    $this->assertDatabaseMissing('products', [
        'name' => 'Milk'
    ]);
});


test('product appears in category page', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'slug' => 'dairy'
    ]);

    $product = Product::factory()->create([
        'name' => 'Milk',
        'category_id' => $category->id,
    ]);

    $this->actingAs($user);

    $response = $this->get('/category/dairy');

    $response->assertStatus(200);
    $response->assertSee('Milk');
});


test('only products of that category are shown', function () {
    $user = User::factory()->create();
    $dairy = Category::factory()->create(['slug' => 'dairy']);
    $fruits = Category::factory()->create(['slug' => 'fruits']);

    Product::factory()->create([
        'name' => 'Milk',
        'category_id' => $dairy->id,
    ]);

    Product::factory()->create([
        'name' => 'Apple',
        'category_id' => $fruits->id,
    ]);

    $this->actingAs($user);

    $response = $this->get('/category/dairy');

    $response->assertSee('Milk');
    $response->assertDontSee('Apple');
});


test('admin can delete product', function () {
    $admin = User::factory()->create([
        'role' => 'admin'
    ]);

    $product = Product::factory()->create();

    $this->actingAs($admin);

    $response = $this->delete("/products/{$product->id}");

    $response->assertStatus(302);

    $this->assertDatabaseMissing('products', [
        'id' => $product->id
    ]);
});


test('user cannot delete product', function () {
    $user = User::factory()->create([
        'role' => 'user'
    ]);

    $product = Product::factory()->create();

    $this->actingAs($user);

    $response = $this->delete("/products/{$product->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('products', [
        'id' => $product->id
    ]);
});

test('product creation requires quantity', function () {
    $admin = User::factory()->create([
        'role' => 'admin'
    ]);

    $category = Category::factory()->create();

    $this->actingAs($admin);

    $response = $this->from('/products')->post('/products', [
        'name' => 'Milk',
        'price' => 50,
        'category_id' => $category->id,
    ]);

    $response->assertRedirect('/products');
    $response->assertSessionHasErrors(['quantity']);

    $this->assertDatabaseMissing('products', [
        'name' => 'Milk',
        'category_id' => $category->id,
    ]);
});

test('admin can update product', function () {
    $admin = User::factory()->create([
        'role' => 'admin'
    ]);

    $product = Product::factory()->create([
        'name' => 'Milk',
        'price' => 50,
        'quantity' => '10',
    ]);

    $this->actingAs($admin);

    $response = $this->put("/products/{$product->id}", [
        'name' => 'Skim Milk',
        'price' => 60,
        'quantity' => '15',
    ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Skim Milk',
        'price' => 60,
        'quantity' => '15',
    ]);
});

test('non admin cannot update product', function () {
    $user = User::factory()->create([
        'role' => 'user'
    ]);

    $product = Product::factory()->create([
        'name' => 'Milk',
        'price' => 50,
        'quantity' => '10',
    ]);

    $this->actingAs($user);

    $response = $this->put("/products/{$product->id}", [
        'name' => 'Skim Milk',
        'price' => 60,
        'quantity' => '15',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Milk',
        'price' => 50,
        'quantity' => '10',
    ]);
});
