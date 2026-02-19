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
            'user_id'      => $user->id,
            'total_amount' => $total,
            'status'       => Order::STATUS_PENDING,
        ]);

        Log::info('Order Created', [
            'order_number' => $order->order_number,
            'amount'       => $order->total_amount,
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
}
