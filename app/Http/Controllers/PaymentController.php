<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function initiatePayment(Request $request)
    {
        $user = auth()->user();

        // Get cart data (adapt if your cart is session-based)
        $cartKey = 'cart_' . auth()->id();
        $cart = session()->get($cartKey, []);

        if (empty($cart)) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Create Order (Pending)
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'total_amount' => $total,
            'status' => Order::STATUS_PENDING,
        ]);

        // Generate Signature
        $signatureData = $this->paymentService->generateSignature($order);

        return view('checkout', [
            'order' => $order,
            'signature' => $signatureData['data']['signature'] ?? null,
            'apiKey' => config('services.omniware.api_key'),
            'amount' => $order->total_amount,
            'baseUrl' => config('services.omniware.base_url'),
        ]);
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
                'signature' => $signatureResponse['data']['signature'] ?? null
            ]);
        } catch (\Exception $e) {

            Log::error('Payment Signature Error', [
                'order_number' => $order->order_number,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function handleReturn(Request $request)
    {
        \Log::info('Omniware Return Payload', $request->all());

        $orderNumber = $request->input('order_id');
        $transactionId = $request->input('transaction_id');
        $responseCode = $request->input('response_code');
        $receivedHash = $request->input('hash');

        if (!$orderNumber) {
            \Log::error('Missing order_id');
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $order = \App\Models\Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            \Log::error('Order not found', ['order_number' => $orderNumber]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Recalculate Hash EXACTLY like Omniware
        |--------------------------------------------------------------------------
        | 1. Remove 'hash' from payload
        | 2. Remove null/empty values
        | 3. Sort alphabetically
        | 4. Start with salt
        | 5. Append values with |
        */

        $payload = $request->except('hash');

        // Remove null / empty values
        $filtered = array_filter($payload, function ($value) {
            return $value !== null && $value !== '';
        });

        // Sort alphabetically by key
        ksort($filtered);

        $salt = config('services.omniware.salt');

        $hashString = $salt;

        foreach ($filtered as $value) {
            $hashString .= '|' . trim((string) $value);
        }

        $calculatedHash = strtoupper(hash('sha512', $hashString));
        $receivedHash = strtoupper($receivedHash);

        if ($calculatedHash !== $receivedHash) {

            \Log::error('Hash mismatch', [
                'hash_string' => $hashString,
                'calculated' => $calculatedHash,
                'received' => $receivedHash
            ]);

            $order->update([
                'status' => \App\Models\Order::STATUS_FAILED
            ]);

            return response()->json(['error' => 'Hash mismatch'], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Payment Status Handling
        |--------------------------------------------------------------------------
        */

        if ((string) $responseCode === '0') {

            $order->update([
                'status' => \App\Models\Order::STATUS_PAID,
                'transaction_id' => $transactionId
            ]);

            // Clear cart after successful payment
            session()->forget('cart_' . $order->user_id);

            \Log::info('Order marked PAID', [
                'order_number' => $orderNumber
            ]);

        } else {

            $order->update([
                'status' => \App\Models\Order::STATUS_FAILED
            ]);

            \Log::info('Order marked FAILED', [
                'order_number' => $orderNumber
            ]);
        }

        return redirect()->route('orders.index')
            ->with('success', 'Payment processed successfully.');
    }


}
