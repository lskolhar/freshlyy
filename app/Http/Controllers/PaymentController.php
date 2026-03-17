<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
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

    /*
    |--------------------------------------------------------------------------
    | STEP 1 — Initiate Payment
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | STEP 2 — Payment Gateway Return (Server Callback)
    |--------------------------------------------------------------------------
    */

    public function handleReturn(Request $request)
    {
        Log::info('Omniware Return Payload', $request->all());

        $verification = $this->paymentService->verifyReturn($request);

        if (!$verification['success']) {
            return response()->json([
                'status' => $verification['status']
            ]);
        }

        $order = $verification['order'];
        $transactionId = $verification['transaction_id'];

        $transaction = $this->transactionService
            ->findByOrderId($order->id);

        if ($transaction) {
            $this->transactionService->markTransactionPaid(
                $transaction->reference_id,
                $transactionId
            );
        }

        return response()->json(['status' => 'success']);
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3 — Browser Redirect After Payment
    |--------------------------------------------------------------------------
    */

    public function handleRedirect(Request $request)
    {
        $orderNumber = $request->input('order_id');

        if (!$orderNumber) {
            return redirect()->route('orders.index')
                ->with('error', 'Invalid payment redirect.');
        }

        $order = $this->orderService->getUserOrder(
            $orderNumber,
            $request->user()->id
        );

        if (!$order) {
            return redirect()->route('orders.index')
                ->with('error', 'Order not found.');
        }

        if ($order->status === 'paid') {

            $this->orderService->clearUserCart($request->user()->id);

            return redirect()->route('orders.index')
                ->with('success', 'Payment successful.');
        }

        return redirect()->route('orders.index')
            ->with('error', 'Payment failed.');
    }
}