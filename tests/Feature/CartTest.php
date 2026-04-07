<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

test('authenticated user can add update remove and clear their cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'name' => 'Banana',
        'price' => 12,
    ]);

    $this->actingAs($user);

    $this->post(route('cart.add', $product))->assertRedirect();
    $this->post(route('cart.add', $product))->assertRedirect();

    expect(session("cart_{$user->id}.{$product->id}.quantity"))->toBe(2);

    $this->patch(route('cart.update', $product), ['quantity' => 5])
        ->assertRedirect();

    expect(session("cart_{$user->id}.{$product->id}.quantity"))->toBe(5);

    $this->delete(route('cart.remove', $product))->assertRedirect();

    expect(session("cart_{$user->id}.{$product->id}"))->toBeNull();

    Order::create([
        'order_number' => 'ORD-CART-CLEAR',
        'user_id' => $user->id,
        'total_amount' => 12,
        'status' => Order::STATUS_PENDING,
    ]);

    session()->put("cart_{$user->id}", [
        $product->id => [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 12.0,
            'quantity' => 1,
        ],
    ]);

    $this->post(route('cart.clear'))
        ->assertRedirect(route('home'));

    expect(session("cart_{$user->id}"))->toBeNull();
    $this->assertDatabaseHas('orders', [
        'order_number' => 'ORD-CART-CLEAR',
        'status' => 'cancelled',
    ]);
});

test('cart update validates quantity and removes item when quantity is zero', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user);
    $this->post(route('cart.add', $product));

    $this->from(route('cart.index'))
        ->patch(route('cart.update', $product), ['quantity' => 'not-a-number'])
        ->assertRedirect(route('cart.index'))
        ->assertSessionHasErrors('quantity');

    $this->patch(route('cart.update', $product), ['quantity' => 0])
        ->assertRedirect();

    expect(session("cart_{$user->id}.{$product->id}"))->toBeNull();
});

test('cart page sorts products by name', function () {
    $user = User::factory()->create();

    session()->put("cart_{$user->id}", [
        1 => ['product_id' => 1, 'name' => 'Zucchini', 'price' => 2.0, 'quantity' => 1],
        2 => ['product_id' => 2, 'name' => 'Apple', 'price' => 1.0, 'quantity' => 1],
    ]);

    $this->actingAs($user)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertSeeInOrder(['Apple', 'Zucchini']);
});
