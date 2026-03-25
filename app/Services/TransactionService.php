<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Create a transaction for an order
     */
    public function createTransaction(Order $order)
    {
        $referenceId = $this->generateReferenceId();

        return Transaction::create([
            'order_id' => $order->id,
            'reference_id' => $referenceId,
            'amount' => $order->total_amount,
            'status' => 'pending',
        ]);
    }

    /**
     * Generate unique reference id
     */
    private function generateReferenceId()
    {
        return 'TXN_' . time() . '_' . strtoupper(Str::random(5));
    }

    
    public function findByReference($referenceId)
    {
        return Transaction::where('reference_id', $referenceId)->first();
    }

    
    public function markTransactionPaid($referenceId, $gatewayTransactionId = null)
    {
        $transaction = $this->findByReference($referenceId);

        if (!$transaction) {
            Log::warning('Transaction not found', [
                'reference_id' => $referenceId
            ]);

            return null;
        }

        if ($transaction->status === 'paid') {

            Log::info('Duplicate callback ignored', [
                'reference_id' => $referenceId
            ]);

            return $transaction;
        }

        $transaction->update([
            'status' => 'paid',
            'gateway_transaction_id' => $gatewayTransactionId
        ]);

        $order = $transaction->order;

        if ($order) {
            $order->update([
                'status' => Order::STATUS_PAID,
                'transaction_id' => $gatewayTransactionId
            ]);
        }

        return $transaction;
    }
    public function findByOrderId($orderId)
    {
        return Transaction::where('order_id', $orderId)->first();
    }
}
