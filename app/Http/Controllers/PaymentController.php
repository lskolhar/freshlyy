<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $transactionService;
    public function __construct(
        PaymentService $paymentService,
        TransactionService $transactionService
    ) {
        $this->paymentService = $paymentService;
        $this->transactionService = $transactionService;
    }
    public function create(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cart = session()->get('cart_' . $user->id, []);

        if (empty($cart)) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        if ($total <= 0) {
            return response()->json(['error' => 'Invalid cart total'], 400);
        }

        $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'total_amount' => $total,
            'status' => Order::STATUS_PENDING,
        ]);

        Log::info('Order Created', [
            'order_number' => $order->order_number,
            'amount' => $order->total_amount,
        ]);

        try {
            $signatureResponse = $this->paymentService->generateSignature($order);

            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'signature' => $signatureResponse['data']['signature'] ?? null,
            ]);
        } catch (\Exception $e) {
            report($e);
            Log::error('Payment Signature Error', [
                'order_number' => $order->order_number,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function initiatePayment(Request $request)
    {
        $user = auth()->user();

        $cartKey = 'cart_' . $user->id;
        $cart = session()->get($cartKey, []);

        if (empty($cart)) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'total_amount' => $total,
            'status' => Order::STATUS_PENDING,
        ]);

        $transaction = $this->transactionService->createTransaction($order);

        foreach ($cart as $productId => $item) {
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $productId,
                'product_name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['price'] * $item['quantity'],
            ]);
        }

        $signatureData = $this->paymentService->generateSignature($order);

        return view('checkout', [
            'order' => $order,
            'transaction' => $transaction,
            'reference_id' => $transaction->reference_id,
            'signature' => $signatureData['data']['signature'] ?? null,
            'apiKey' => config('services.omniware.api_key'),
            'amount' => $order->total_amount,
            'baseUrl' => config('services.omniware.base_url'),
        ]);
    }


    // SERVER-TO-SERVER VERIFICATION
    public function handleReturn(Request $request)
    {
        Log::info('Omniware Return Payload', $request->all());

        $orderNumber = $request->input('order_id');
        $transactionId = $request->input('transaction_id');
        $responseCode = $request->input('response_code');
        $amount = $request->input('amount');
        $receivedHash = strtoupper($request->input('hash'));
        $referenceId = $request->input('reference_id');

        /*
        |--------------------------------------------------------------------------
        | STEP 1 — Find Transaction using reference_id
        |--------------------------------------------------------------------------
        */

        $order = null;
        $transaction = null;

        if ($orderNumber) {

            $order = Order::where('order_number', $orderNumber)->first();

            if ($order) {
                $transaction = Transaction::where('order_id', $order->id)->first();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FALLBACK — Use order_number if reference not found
        |--------------------------------------------------------------------------
        */

        if (!$order && $orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        }

        if (!$order) {
            Log::warning('Invalid Order Attempt', [
                'order_number' => $orderNumber,
                'reference_id' => $referenceId
            ]);

            return response()->json(['error' => 'Order not found'], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 2 — Prevent duplicate processing
        |--------------------------------------------------------------------------
        */

        if ($order->status === Order::STATUS_PAID) {
            return response()->json(['status' => 'already_processed']);
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 3 — Verify Amount
        |--------------------------------------------------------------------------
        */

        if ((float) $order->total_amount !== (float) $amount) {

            $order->update([
                'status' => Order::STATUS_FAILED,
                'payment_response' => json_encode($request->all()),
            ]);

            Log::error('Amount mismatch', [
                'order_number' => $orderNumber,
                'expected' => $order->total_amount,
                'received' => $amount,
            ]);

            return response()->json(['status' => 'amount_mismatch']);
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 4 — Regenerate Hash
        |--------------------------------------------------------------------------
        */

        $payload = $request->except('hash');

        $filtered = array_filter($payload, function ($value) {
            return $value !== null && $value !== '';
        });

        ksort($filtered);

        $salt = config('services.omniware.salt');
        $hashString = $salt;

        foreach ($filtered as $value) {
            $hashString .= '|' . trim((string) $value);
        }

        $calculatedHash = strtoupper(hash('sha512', $hashString));

        /*
        |--------------------------------------------------------------------------
        | STEP 5 — Compare Hash
        |--------------------------------------------------------------------------
        */

        if ($calculatedHash !== $receivedHash) {

            $order->update([
                'status' => Order::STATUS_FAILED,
                'payment_response' => json_encode($request->all()),
            ]);

            Log::error('Hash verification failed', [
                'order_number' => $orderNumber,
            ]);

            return response()->json(['status' => 'hash_failed']);
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 6 — Payment Success
        |--------------------------------------------------------------------------
        */

        if ((string) $responseCode === '0') {

            // Update using TransactionService if transaction exists
            if ($transaction) {

                $this->transactionService->markTransactionPaid(
                    $transaction->reference_id,
                    $transactionId
                );

            } else {

                // fallback if transaction missing
                $order->update([
                    'status' => Order::STATUS_PAID,
                    'transaction_id' => $transactionId,
                    'payment_response' => json_encode($request->all()),
                ]);

            }

            return response()->json(['status' => 'success']);
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 7 — Payment Failed
        |--------------------------------------------------------------------------
        */

        $order->update([
            'status' => Order::STATUS_FAILED,
            'payment_response' => json_encode($request->all()),
        ]);

        return response()->json(['status' => 'failed']);
    }

    // BROWSER REDIRECT HANDLER
    public function handleRedirect(Request $request)
    {
        $orderNumber = $request->input('order_id');

        // Log session ID and cart contents before clearing
        $sessionId = session()->getId();
        $cartKey = 'cart_' . auth()->id();
        $cartBefore = session()->get($cartKey);
        \Log::debug('Payment Redirect Debug', [
            'session_id' => $sessionId,
            'cart_key' => $cartKey,
            'cart_before' => $cartBefore,
            'order_number' => $orderNumber,
            'user_id' => auth()->id(),
        ]);

        if (!$orderNumber) {
            \Log::warning('Payment Redirect: Invalid order_id', [
                'session_id' => $sessionId,
                'order_id' => $orderNumber,
            ]);

            return redirect()->route('orders.index')
                ->with('error', 'Invalid payment redirect.');
        }

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            \Log::warning('Payment Redirect: Order not found', [
                'session_id' => $sessionId,
                'order_id' => $orderNumber,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('orders.index')
                ->with('error', 'Order not found.');
        }

        if ($order->status === Order::STATUS_PAID) {
            // Clear the cart and log after
            session()->forget($cartKey);
            $cartAfter = session()->get($cartKey);
            \Log::info('Cart cleared after payment', [
                'session_id' => $sessionId,
                'cart_key' => $cartKey,
                'cart_after' => $cartAfter,
                'order_number' => $orderNumber,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('orders.index')
                ->with('success', 'Payment successful.');
        }

        \Log::warning('Payment Redirect: Payment failed', [
            'session_id' => $sessionId,
            'order_id' => $orderNumber,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('orders.index')
            ->with('error', 'Payment failed.');
    }
}
