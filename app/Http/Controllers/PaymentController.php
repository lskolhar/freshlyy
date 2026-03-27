<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $transactionService;
    protected $orderService;

    public function __construct(
        PaymentService $paymentService,
        TransactionService $transactionService,
        OrderService $orderService
    ) {
        $this->paymentService = $paymentService;
        $this->transactionService = $transactionService;
        $this->orderService = $orderService;
    }

    public function initiatePayment(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $cart = $this->orderService->getUserCart($user->id);

        if (empty($cart)) {
            return redirect()->back()->with('error', 'Cart is empty.');
        }

        $order = $this->orderService->createOrderFromCart($user->id, $cart);

        $transaction = $this->transactionService->createTransaction($order);
        $paymentToken = (string) Str::uuid();

        $request->session()->put('payment_confirmation', [
            'order_number' => $order->order_number,
            'reference_id' => $transaction->reference_id,
            'token' => $paymentToken,
        ]);

        $signatureData = $this->paymentService->generateSignature($order);

        return view('checkout', [
            'order' => $order,
            'transaction' => $transaction,
            'reference_id' => $transaction->reference_id,
            'signature' => $signatureData['data']['signature'] ?? null,
            'apiKey' => config('services.omniware.api_key'),
            'amount' => $order->total_amount,
            'baseUrl' => config('services.omniware.base_url'),
            'paymentToken' => $paymentToken,
        ]);
    }

    public function handleReturn(Request $request)
    {
        Log::info('RETURN HIT - Omniware Payload', [
            'order_id' => $request->input('order_id'),
            'transaction_id' => $request->input('transaction_id'),
            'response_code' => $request->input('response_code'),
            'amount' => $request->input('amount'),
        ]);

        $verification = $this->paymentService->verifyReturn($request);

        if (!$verification['success']) {

            Log::error('Payment verification failed', [
                'reason' => $verification['status'],
                'order_id' => $request->input('order_id'),
                'transaction_id' => $request->input('transaction_id'),
            ]);

            return response()->json([
                'status' => $verification['status']
            ]);
        }

        $order = $verification['order'];
        $gatewayTransactionId = $verification['transaction_id'];

        $transaction = Transaction::where('order_id', $order->id)->first();

        if (!$transaction) {

            Log::error('Transaction not found using order_id', [
                'order_id' => $order->id
            ]);

            return response()->json([
                'status' => 'transaction_not_found'
            ]);
        }

        if ($transaction->status === 'paid') {

            Log::info('Transaction already paid (duplicate callback)', [
                'order_id' => $order->id
            ]);

            return response()->json([
                'status' => 'already_processed'
            ]);
        }

        $this->transactionService->markTransactionPaid(
            $transaction->reference_id,
            $gatewayTransactionId
        );

        Log::info('Transaction marked as PAID', [
            'order_id' => $order->id,
            'gateway_transaction_id' => $gatewayTransactionId
        ]);

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function handleRedirect(Request $request)
    {
        $orderNumber = $request->input('order_id');

        if (!$orderNumber) {
            return redirect()->route('orders.index')
                ->with('error', 'Invalid payment redirect.');
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return redirect()->route('orders.index')
                ->with('error', 'Order not found.');
        }

        if (Auth::id() !== $order->user_id && Auth::user()?->role !== 'admin') {
            abort(403);
        }

        if ($order->status === Order::STATUS_PAID) {

            $transaction = Transaction::where('order_id', $order->id)->first();

            if ($transaction && $transaction->status !== 'paid') {

                $this->transactionService->markTransactionPaid(
                    $transaction->reference_id,
                    $order->transaction_id ?? 'REDIRECT_' . time()
                );

                Log::info('Transaction fixed via redirect', [
                    'order_id' => $order->id
                ]);
            }

            if (Auth::check()) {
                $this->orderService->clearUserCart(Auth::id());
            }

            $request->session()->forget('payment_confirmation');

            return redirect()->route('orders.index')
                ->with('success', 'Payment successful.');
        }

        return redirect()->route('orders.index')
            ->with('error', 'Payment failed.');
    }
    public function confirmPayment(Request $request)
    {
        Log::info('Frontend payment confirmation', [
            'order_id' => $request->input('order_id'),
            'user_id' => $request->user()?->id,
        ]);

        $orderNumber = $request->input('order_id');
        $gatewayTransactionId = $request->input('transaction_id');
        $paymentToken = $request->input('payment_token');

        if (!$orderNumber || !$gatewayTransactionId || !$paymentToken) {
            return response()->json(['status' => 'invalid_request'], 422);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json(['status' => 'order_not_found']);
        }

        if ($request->user()->id !== $order->user_id && $request->user()->role !== 'admin') {
            abort(403);
        }

        $transaction = Transaction::where('order_id', $order->id)->first();

        if (!$transaction) {
            return response()->json(['status' => 'transaction_not_found']);
        }

        $sessionPayment = $request->session()->get('payment_confirmation');

        if (
            !is_array($sessionPayment)
            || ($sessionPayment['order_number'] ?? null) !== $order->order_number
            || ($sessionPayment['reference_id'] ?? null) !== $transaction->reference_id
            || !hash_equals((string) ($sessionPayment['token'] ?? ''), (string) $paymentToken)
        ) {
            return response()->json(['status' => 'payment_context_mismatch'], 403);
        }

        if ($transaction->status === 'paid') {
            return response()->json(['status' => 'already_paid']);
        }

        $this->transactionService->markTransactionPaid(
            $transaction->reference_id,
            $gatewayTransactionId
        );

        Log::info('Transaction marked via frontend fallback', [
            'order_id' => $order->id
        ]);

        $request->session()->forget('payment_confirmation');

        return response()->json(['status' => 'success']);
    }
    public function checkStatus(Request $request)
{
    $order = Order::where('order_number', $request->order_id)->first();

    if (!$order) {
        return response()->json(['status' => 'not_found']);
    }

    if ($request->user()->id !== $order->user_id && $request->user()->role !== 'admin') {
        abort(403);
    }

    return response()->json([
        'status' => $order->status === 'paid' ? 'paid' : 'pending'
    ]);
}
}
