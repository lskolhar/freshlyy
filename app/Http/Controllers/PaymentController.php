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

    // SERVER-TO-SERVER VERIFICATION
    public function handleReturn(Request $request)
    {
        Log::info('Omniware Return Payload', $request->all());

        $orderNumber   = $request->input('order_id');
        $transactionId = $request->input('transaction_id');
        $responseCode  = $request->input('response_code');
        $amount        = $request->input('amount');
        $receivedHash  = strtoupper($request->input('hash'));

        if (!$orderNumber) {
            return response()->json(['error' => 'Invalid order'], 400);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->status === Order::STATUS_PAID) {
            return response()->json(['status' => 'already_processed']);
        }

        if ((float)$order->total_amount !== (float)$amount) {

            $order->update([
                'status' => Order::STATUS_FAILED,
                'payment_response' => json_encode($request->all())
            ]);

            return response()->json(['status' => 'amount_mismatch']);
        }

        $payload = $request->except('hash');

        $filtered = array_filter($payload, function ($value) {
            return $value !== null && $value !== '';
        });

        ksort($filtered);

        $salt = config('services.omniware.salt');
        $hashString = $salt;

        foreach ($filtered as $value) {
            $hashString .= '|' . trim((string)$value);
        }

        $calculatedHash = strtoupper(hash('sha512', $hashString));

        if ($calculatedHash !== $receivedHash) {

            $order->update([
                'status' => Order::STATUS_FAILED,
                'payment_response' => json_encode($request->all())
            ]);

            return response()->json(['status' => 'hash_failed']);
        }

        if ((string)$responseCode === '0') {

            $order->update([
                'status' => Order::STATUS_PAID,
                'transaction_id' => $transactionId,
                'payment_response' => json_encode($request->all())
            ]);

            return response()->json(['status' => 'success']);
        }

        $order->update([
            'status' => Order::STATUS_FAILED,
            'payment_response' => json_encode($request->all())
        ]);

        return response()->json(['status' => 'failed']);
    }

    // BROWSER REDIRECT HANDLER
    public function handleRedirect(Request $request)
    {
        $orderNumber = $request->input('order_id');

        if (!$orderNumber) {
            return redirect()->route('orders.index')
                ->with('error', 'Invalid payment redirect.');
        }

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return redirect()->route('orders.index')
                ->with('error', 'Order not found.');
        }

        if ($order->status === Order::STATUS_PAID) {

            session()->forget('cart_' . auth()->id());

            return redirect()->route('orders.index')
                ->with('success', 'Payment successful.');
        }

        return redirect()->route('orders.index')
            ->with('error', 'Payment failed.');
    }
}