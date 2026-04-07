<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Http\Controllers\PaymentController;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

function omniwareHash(array $payload, string $salt = 'test-salt'): string
{
    $filtered = [];

    foreach ($payload as $key => $value) {
        if ($key !== 'hash' && $value !== null && $value !== '') {
            $filtered[$key] = trim((string) $value);
        }
    }

    ksort($filtered);

    return strtoupper(hash('sha512', $salt.'|'.implode('|', $filtered)));
}

test('payment service generates signature through omniware api', function () {
    putenv('OMNIWARE_API_KEY=test-key');
    putenv('OMNIWARE_RETURN_URL=https://freshlyy.test/return');
    putenv('OMNIWARE_MODE=test');
    putenv('OMNIWARE_SALT=test-salt');
    putenv('OMNIWARE_BASE_URL=https://omniware.test');

    Http::fake([
        '*' => Http::response([
            'data' => ['signature' => 'signed-request'],
        ]),
    ]);

    $order = Order::create([
        'order_number' => 'ORD-SIGN',
        'user_id' => User::factory()->create(['name' => 'Buyer', 'email' => 'buyer@example.com'])->id,
        'total_amount' => 123.45,
        'status' => Order::STATUS_PENDING,
    ]);

    $signature = app(PaymentService::class)->generateSignature($order);

    expect($signature['data']['signature'])->toBe('signed-request');

    Http::assertSent(fn ($request) => $request['order_id'] === 'ORD-SIGN'
        && $request['amount'] === '123.45'
        && $request['hash'] !== null);
});

test('payment service throws for failed malformed or error signature responses', function () {
    $order = Order::create([
        'order_number' => 'ORD-SIGN-FAIL',
        'user_id' => User::factory()->create()->id,
        'total_amount' => 123.45,
        'status' => Order::STATUS_PENDING,
    ]);

    Http::fakeSequence()
        ->push([], 500)
        ->push(['error' => ['message' => 'Rejected']])
        ->push(['data' => []]);

    expect(fn () => app(PaymentService::class)->generateSignature($order))
        ->toThrow(Exception::class, 'Signature API HTTP Error');

    expect(fn () => app(PaymentService::class)->generateSignature($order))
        ->toThrow(Exception::class, 'Omniware Error: Rejected');

    expect(fn () => app(PaymentService::class)->generateSignature($order))
        ->toThrow(Exception::class, 'Signature missing in response');
});

test('payment service verifies return success and failure states', function () {
    config(['services.omniware.salt' => 'test-salt']);

    $order = Order::create([
        'order_number' => 'ORD-VERIFY',
        'user_id' => User::factory()->create()->id,
        'total_amount' => 50,
        'status' => Order::STATUS_PENDING,
    ]);

    $payload = [
        'order_id' => $order->order_number,
        'transaction_id' => 'GW-VERIFY',
        'response_code' => '0',
        'amount' => '50.00',
    ];

    $request = Request::create('/payment/callback', 'POST', [
        ...$payload,
        'hash' => omniwareHash($payload),
    ]);

    $result = app(PaymentService::class)->verifyReturn($request);

    expect($result['success'])->toBeTrue()
        ->and($result['transaction_id'])->toBe('GW-VERIFY');

    $badAmount = Request::create('/payment/callback', 'POST', [
        ...$payload,
        'amount' => '99.00',
        'hash' => 'bad-hash',
    ]);

    expect(app(PaymentService::class)->verifyReturn($badAmount)['status'])->toBe('amount_mismatch');

    $badHash = Request::create('/payment/callback', 'POST', [
        ...$payload,
        'hash' => 'bad-hash',
    ]);

    expect(app(PaymentService::class)->verifyReturn($badHash)['status'])->toBe('hash_failed');

    $failurePayload = [
        'order_id' => $order->order_number,
        'transaction_id' => 'GW-FAIL',
        'response_code' => '1',
        'amount' => '50.00',
    ];

    $failedRequest = Request::create('/payment/callback', 'POST', [
        ...$failurePayload,
        'hash' => omniwareHash($failurePayload),
    ]);

    expect(app(PaymentService::class)->verifyReturn($failedRequest)['status'])->toBe('payment_failed');

    $missingOrder = Request::create('/payment/callback', 'POST', [
        'order_id' => 'ORD-MISSING',
        'transaction_id' => 'GW-MISSING',
        'response_code' => '0',
        'amount' => '50.00',
        'hash' => 'unused',
    ]);

    expect(app(PaymentService::class)->verifyReturn($missingOrder)['status'])->toBe('order_not_found');

    $order->update(['status' => Order::STATUS_PAID]);

    expect(app(PaymentService::class)->verifyReturn($request)['status'])->toBe('already_processed');
});

test('payment controller handle return covers failure missing duplicate and success callbacks', function () {
    $order = Order::create([
        'order_number' => 'ORD-CALLBACK',
        'user_id' => User::factory()->create()->id,
        'total_amount' => 50,
        'status' => Order::STATUS_PENDING,
    ]);

    $paymentService = \Mockery::mock(PaymentService::class);
    $controller = new PaymentController(
        $paymentService,
        app(TransactionService::class),
        app(OrderService::class),
    );

    $request = Request::create('/callback', 'POST', [
        'order_id' => $order->order_number,
        'transaction_id' => 'GW-CALLBACK',
        'response_code' => '0',
        'amount' => '50.00',
    ]);

    $paymentService->shouldReceive('verifyReturn')
        ->once()
        ->andReturn(['success' => false, 'status' => 'hash_failed']);

    expect($controller->handleReturn($request)->getData(true))->toBe(['status' => 'hash_failed']);

    $paymentService->shouldReceive('verifyReturn')
        ->once()
        ->andReturn(['success' => true, 'order' => $order, 'transaction_id' => 'GW-CALLBACK']);

    expect($controller->handleReturn($request)->getData(true))->toBe(['status' => 'transaction_not_found']);

    $paidTransaction = Transaction::create([
        'order_id' => $order->id,
        'reference_id' => 'TXN-CALLBACK-PAID',
        'amount' => 50,
        'status' => 'paid',
    ]);

    $paymentService->shouldReceive('verifyReturn')
        ->once()
        ->andReturn(['success' => true, 'order' => $order, 'transaction_id' => 'GW-CALLBACK']);

    expect($controller->handleReturn($request)->getData(true))->toBe(['status' => 'already_processed']);

    $paidTransaction->update([
        'reference_id' => 'TXN-CALLBACK-SUCCESS',
        'status' => 'pending',
    ]);

    $paymentService->shouldReceive('verifyReturn')
        ->once()
        ->andReturn(['success' => true, 'order' => $order, 'transaction_id' => 'GW-CALLBACK']);

    expect($controller->handleReturn($request)->getData(true))->toBe(['status' => 'success']);

    $this->assertDatabaseHas('transactions', [
        'id' => $paidTransaction->id,
        'status' => 'paid',
        'gateway_transaction_id' => 'GW-CALLBACK',
    ]);
});

test('payment controller rejects initiating payment without a user', function () {
    expect(fn () => app(PaymentController::class)->initiatePayment(Request::create('/checkout', 'POST')))
        ->toThrow(HttpException::class);
});

test('checkout initiation creates order transaction and payment context', function () {
    putenv('OMNIWARE_API_KEY=test-key');
    putenv('OMNIWARE_RETURN_URL=https://freshlyy.test/return');
    putenv('OMNIWARE_MODE=test');
    putenv('OMNIWARE_SALT=test-salt');
    putenv('OMNIWARE_BASE_URL=https://omniware.test');
    config([
        'services.omniware.api_key' => 'test-key',
        'services.omniware.base_url' => 'https://omniware.test',
    ]);

    Http::fake([
        '*' => Http::response([
            'data' => ['signature' => 'signed-request'],
        ]),
    ]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Apple', 'price' => 25]);

    session()->put("cart_{$user->id}", [
        $product->id => ['name' => 'Apple', 'price' => 25, 'quantity' => 2],
    ]);

    $this->actingAs($user)
        ->post(route('checkout.initiate'))
        ->assertOk()
        ->assertSee('signed-request', false);

    $order = Order::where('user_id', $user->id)->first();
    $transaction = Transaction::where('order_id', $order->id)->first();

    expect(session('payment_confirmation.order_number'))->toBe($order->order_number)
        ->and(session('payment_confirmation.reference_id'))->toBe($transaction->reference_id)
        ->and(session('payment_confirmation.token'))->not->toBeEmpty();
});

test('checkout initiation rejects an empty cart', function () {
    $this->actingAs(User::factory()->create())
        ->from(route('cart.index'))
        ->post(route('checkout.initiate'))
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('error', 'Cart is empty.');
});

test('payment confirmation validates context and marks transaction paid', function () {
    $user = User::factory()->create();
    $order = Order::create([
        'order_number' => 'ORD-CONFIRM',
        'user_id' => $user->id,
        'total_amount' => 75,
        'status' => Order::STATUS_PENDING,
    ]);
    $transaction = Transaction::create([
        'order_id' => $order->id,
        'reference_id' => 'TXN-CONFIRM',
        'amount' => 75,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->withSession([
            'payment_confirmation' => [
                'order_number' => $order->order_number,
                'reference_id' => $transaction->reference_id,
                'token' => 'secret-token',
            ],
        ])
        ->postJson('/payment/confirm', [
            'order_id' => $order->order_number,
            'transaction_id' => 'GW-CONFIRM',
            'payment_token' => 'secret-token',
        ])
        ->assertOk()
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'status' => 'paid',
        'gateway_transaction_id' => 'GW-CONFIRM',
    ]);

    expect(session('payment_confirmation'))->toBeNull();
});

test('payment confirmation handles invalid request missing transaction mismatch and duplicate states', function () {
    $user = User::factory()->create();
    $order = Order::create([
        'order_number' => 'ORD-CONFIRM-FAIL',
        'user_id' => $user->id,
        'total_amount' => 75,
        'status' => Order::STATUS_PENDING,
    ]);

    $this->actingAs($user)
        ->postJson('/payment/confirm', [])
        ->assertStatus(422)
        ->assertJson(['status' => 'invalid_request']);

    $this->postJson('/payment/confirm', [
        'order_id' => 'MISSING-ORDER',
        'transaction_id' => 'GW',
        'payment_token' => 'token',
    ])->assertOk()->assertJson(['status' => 'order_not_found']);

    $this->postJson('/payment/confirm', [
        'order_id' => $order->order_number,
        'transaction_id' => 'GW',
        'payment_token' => 'token',
    ])->assertOk()->assertJson(['status' => 'transaction_not_found']);

    $this->actingAs(User::factory()->create())
        ->postJson('/payment/confirm', [
            'order_id' => $order->order_number,
            'transaction_id' => 'GW',
            'payment_token' => 'token',
        ])->assertForbidden();

    $transaction = Transaction::create([
        'order_id' => $order->id,
        'reference_id' => 'TXN-CONFIRM-FAIL',
        'amount' => 75,
        'status' => 'paid',
    ]);

    $this->actingAs($user)
        ->withSession([
        'payment_confirmation' => [
            'order_number' => $order->order_number,
            'reference_id' => $transaction->reference_id,
            'token' => 'right-token',
        ],
    ])->postJson('/payment/confirm', [
        'order_id' => $order->order_number,
        'transaction_id' => 'GW',
        'payment_token' => 'wrong-token',
    ])->assertForbidden()->assertJson(['status' => 'payment_context_mismatch']);

    $this->withSession([
        'payment_confirmation' => [
            'order_number' => $order->order_number,
            'reference_id' => $transaction->reference_id,
            'token' => 'right-token',
        ],
    ])->postJson('/payment/confirm', [
        'order_id' => $order->order_number,
        'transaction_id' => 'GW',
        'payment_token' => 'right-token',
    ])->assertOk()->assertJson(['status' => 'already_paid']);
});

test('payment redirect and status endpoints handle success failure and authorization', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $paidOrder = Order::create([
        'order_number' => 'ORD-REDIRECT-PAID',
        'user_id' => $user->id,
        'total_amount' => 30,
        'status' => Order::STATUS_PAID,
    ]);
    Transaction::create([
        'order_id' => $paidOrder->id,
        'reference_id' => 'TXN-REDIRECT',
        'amount' => 30,
        'status' => 'pending',
    ]);
    $pendingOrder = Order::create([
        'order_number' => 'ORD-REDIRECT-PENDING',
        'user_id' => $user->id,
        'total_amount' => 30,
        'status' => Order::STATUS_PENDING,
    ]);

    session()->put("cart_{$user->id}", ['item' => true]);

    $this->actingAs($user)
        ->get('/payment/return?order_id='.$paidOrder->order_number)
        ->assertRedirect(route('orders.index'))
        ->assertSessionHas('success', 'Payment successful.');

    expect(session("cart_{$user->id}"))->toBeNull();

    $this->get('/payment/return?order_id='.$pendingOrder->order_number)
        ->assertRedirect(route('orders.index'))
        ->assertSessionHas('error', 'Payment failed.');

    $this->get('/payment/return')
        ->assertRedirect(route('orders.index'))
        ->assertSessionHas('error', 'Invalid payment redirect.');

    $this->get('/payment/return?order_id=missing-order')
        ->assertRedirect(route('orders.index'))
        ->assertSessionHas('error', 'Order not found.');

    $this->actingAs($otherUser)
        ->get('/payment/return?order_id='.$paidOrder->order_number)
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/payment/check-status?order_id='.$paidOrder->order_number)
        ->assertOk()
        ->assertJson(['status' => 'paid']);

    $this->actingAs($otherUser)
        ->get('/payment/check-status?order_id='.$paidOrder->order_number)
        ->assertForbidden();

    $this->get('/payment/check-status?order_id=missing')
        ->assertOk()
        ->assertJson(['status' => 'not_found']);
});
