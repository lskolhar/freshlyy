<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::post('/orders-test', [OrderController::class, 'store']);
});

test('guest and empty cart cannot place an order', function () {
    $this->post('/orders-test')
        ->assertRedirect()
        ->assertSessionHas('error', 'Please login first.');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/orders-test')
        ->assertRedirect()
        ->assertSessionHas('error', 'Cart is empty.');
});

test('user can place an order from their cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'name' => 'Milk',
        'price' => 50,
    ]);

    session()->put("cart_{$user->id}", [
        $product->id => [
            'name' => 'Milk',
            'price' => 50,
            'quantity' => 2,
        ],
    ]);

    $this->actingAs($user)
        ->post('/orders-test')
        ->assertRedirect('/')
        ->assertSessionHas('success', 'Order placed successfully.');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'total_amount' => 100,
        'status' => Order::STATUS_PENDING,
    ]);

    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'product_name' => 'Milk',
        'quantity' => 2,
        'subtotal' => 100,
    ]);

    expect(session("cart_{$user->id}"))->toBeNull();
});

test('orders index scopes regular users and clears cart after paid order', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Order::create([
        'order_number' => 'ORD-USER-PAID',
        'user_id' => $user->id,
        'total_amount' => 10,
        'status' => Order::STATUS_PAID,
    ]);

    Order::create([
        'order_number' => 'ORD-OTHER',
        'user_id' => $otherUser->id,
        'total_amount' => 20,
        'status' => Order::STATUS_PENDING,
    ]);

    session()->put("cart_{$user->id}", ['sample' => true]);

    $this->actingAs($user)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ORD-USER-PAID')
        ->assertDontSee('ORD-OTHER');

    expect(session("cart_{$user->id}"))->toBeNull();
});

test('admin can list orders and update status while users cannot', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    $order = Order::create([
        'order_number' => 'ORD-STATUS',
        'user_id' => $user->id,
        'total_amount' => 15,
        'status' => Order::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('ORD-STATUS');

    $this->actingAs($user)
        ->patch(route('orders.updateStatus', $order), ['status' => Order::STATUS_PAID])
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('orders.updateStatus', $order), ['status' => Order::STATUS_PAID])
        ->assertRedirect()
        ->assertSessionHas('success', 'Order status updated.');

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => Order::STATUS_PAID,
    ]);
});

test('cancelled orders cannot be modified', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::create([
        'order_number' => 'ORD-CANCELLED',
        'user_id' => User::factory()->create()->id,
        'total_amount' => 15,
        'status' => Order::STATUS_CANCELLED,
    ]);

    $this->actingAs($admin)
        ->patch(route('orders.updateStatus', $order), ['status' => Order::STATUS_PAID])
        ->assertRedirect()
        ->assertSessionHas('error', 'Cancelled orders cannot be modified.');
});
