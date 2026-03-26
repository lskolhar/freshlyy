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

    public function handleReturn(Request $request)
    {
        // ✅ Log full payload
        Log::info('RETURN HIT - Omniware Payload', $request->all());

        // ✅ Step 1: Verify response
        $verification = $this->paymentService->verifyReturn($request);

        if (!$verification['success']) {

            Log::error('Payment verification failed', [
                'reason' => $verification['status'],
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => $verification['status']
            ]);
        }

        // ✅ Step 2: Extract verified data
        $order = $verification['order'];
        $gatewayTransactionId = $verification['transaction_id'];

        // ✅ Step 3: Fetch transaction using ORDER ID (FIXED)
        $transaction = Transaction::where('order_id', $order->id)->first();

        if (!$transaction) {

            Log::error('Transaction not found using order_id', [
                'order_id' => $order->id
            ]);

            return response()->json([
                'status' => 'transaction_not_found'
            ]);
        }

        // ✅ Step 4: Prevent duplicate update
        if ($transaction->status === 'paid') {

            Log::info('Transaction already paid (duplicate callback)', [
                'order_id' => $order->id
            ]);

            return response()->json([
                'status' => 'already_processed'
            ]);
        }

        // ✅ Step 5: Mark as PAID
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

        // 🔥 KEY FIX — DO NOT VERIFY AGAIN
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

            return redirect()->route('orders.index')
                ->with('success', 'Payment successful.');
        }

        return redirect()->route('orders.index')
            ->with('error', 'Payment failed.');
    }
    public function confirmPayment(Request $request)
    {
        Log::info('Frontend payment confirmation', $request->all());

        $orderNumber = $request->input('order_id');
        $gatewayTransactionId = $request->input('transaction_id');

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json(['status' => 'order_not_found']);
        }

        $transaction = Transaction::where('order_id', $order->id)->first();

        if (!$transaction) {
            return response()->json(['status' => 'transaction_not_found']);
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

        return response()->json(['status' => 'success']);
    }
    public function checkStatus(Request $request)
{
    $order = Order::where('order_number', $request->order_id)->first();

    if (!$order) {
        return response()->json(['status' => 'not_found']);
    }

    return response()->json([
        'status' => $order->status === 'paid' ? 'paid' : 'pending'
    ]);
}
}
