<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\HashService;
use App\Services\OrderService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

test('hash service filters sorts trims and hashes omniware parameters', function () {
    $hash = app(HashService::class)->generate([
        'zeta' => ' last ',
        'empty' => '',
        'alpha' => ' first ',
        'null' => null,
    ], 'salt');

    expect($hash)->toBe(strtoupper(hash('sha512', 'salt|first|last')));
});

test('order service reads clears creates and finds user orders', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Curd', 'price' => 30]);
    $service = app(OrderService::class);

    session()->put("cart_{$user->id}", [
        $product->id => ['name' => 'Curd', 'price' => 30, 'quantity' => 3],
    ]);

    expect($service->getUserCart($user->id))->not->toBeEmpty();

    $order = $service->createOrderFromCart($user->id, $service->getUserCart($user->id));

    expect($order->total_amount)->toBe('90.00')
        ->and($service->getUserOrder($order->order_number, $user->id)?->is($order))->toBeTrue();

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'subtotal' => 90,
    ]);

    $service->clearUserCart($user->id);

    expect($service->getUserCart($user->id))->toBe([]);
});

test('transaction service creates finds marks paid ignores duplicates and handles missing references', function () {
    $order = Order::create([
        'order_number' => 'ORD-TXN',
        'user_id' => User::factory()->create()->id,
        'total_amount' => 42,
        'status' => Order::STATUS_PENDING,
    ]);

    $service = app(TransactionService::class);
    $transaction = $service->createTransaction($order);

    expect($transaction->reference_id)->toStartWith('TXN_')
        ->and($service->findByReference($transaction->reference_id)?->is($transaction))->toBeTrue()
        ->and($service->findByOrderId($order->id)?->is($transaction))->toBeTrue();

    $paid = $service->markTransactionPaid($transaction->reference_id, 'GW-123');

    expect($paid?->status)->toBe('paid')
        ->and($service->markTransactionPaid($transaction->reference_id, 'GW-456')?->id)->toBe($transaction->id)
        ->and($service->markTransactionPaid('missing-reference'))->toBeNull();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => Order::STATUS_PAID,
        'transaction_id' => 'GW-123',
    ]);
});

test('models expose their relationships and user initials', function () {
    $user = User::factory()->create(['name' => 'Fresh Buyer']);
    $product = Product::factory()->create();
    $order = Order::create([
        'order_number' => 'ORD-REL',
        'user_id' => $user->id,
        'total_amount' => 20,
        'status' => Order::STATUS_PENDING,
    ]);
    $item = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'price' => 20,
        'quantity' => 1,
        'subtotal' => 20,
    ]);
    $transaction = Transaction::create([
        'order_id' => $order->id,
        'reference_id' => 'TXN-REL',
        'amount' => 20,
        'status' => 'pending',
    ]);

    expect($user->initials())->toBe('FB')
        ->and($user->orders()->first()?->is($order))->toBeTrue()
        ->and($order->user?->is($user))->toBeTrue()
        ->and($order->orderItems()->first()?->is($item))->toBeTrue()
        ->and($order->transactions()->first()?->is($transaction))->toBeTrue()
        ->and($item->order?->is($order))->toBeTrue()
        ->and($item->product?->is($product))->toBeTrue()
        ->and($product->category)->not->toBeNull()
        ->and($transaction->order?->is($order))->toBeTrue();
});

test('fortify create new user validates profile and hashes password cast', function () {
    $creator = app(CreateNewUser::class);

    $user = $creator->create([
        'name' => 'New Customer',
        'email' => 'customer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and(Hash::check('password', $user->password))->toBeTrue();

    expect(fn () => $creator->create([
        'name' => '',
        'email' => 'customer@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]))->toThrow(ValidationException::class);
});
