<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

class PaymentService
{
    protected $hashService;

    public function __construct(HashService $hashService)
    {
        $this->hashService = $hashService;
    }


    public function generateSignature(Order $order): array
    {
        $user = $order->user;

        $params = [
            'api_key' => env('OMNIWARE_API_KEY'),
            'return_url' => env('OMNIWARE_RETURN_URL'),
            'mode' => env('OMNIWARE_MODE'),
            'order_id' => $order->order_number,
            'amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'currency' => 'INR',
            'description' => 'Freshlyy Order Payment',
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '9999999999',
            'address_line_1' => 'Test address',
            'address_line_2' => '',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'zip_code' => '560043',
            'country' => 'India',
        ];

        $hash = $this->hashService->generate(
            $params,
            env('OMNIWARE_SALT')
        );

        $params['hash'] = $hash;

        Log::info('Omniware Signature Request', [
            'order_number' => $order->order_number,
            'amount' => $order->total_amount,
        ]);

        /** @var Response $response */
        $response = Http::timeout(30)
            ->post(
                env('OMNIWARE_BASE_URL') . '/v2/getpaymentrequestsignature',
                $params
            );

        Log::info('Omniware Signature Response', [
            'status' => $response->status(),
        ]);

        if (!$response->successful()) {
            throw new \Exception('Signature API HTTP Error');
        }

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            throw new \Exception(
                'Omniware Error: ' . ($responseData['error']['message'] ?? 'Unknown error')
            );
        }

        if (!isset($responseData['data']['signature'])) {
            throw new \Exception('Signature missing in response');
        }

        return $responseData;
    }
    public function verifyReturn($request)
    {
        $orderNumber = $request->input('order_id');
        $transactionId = $request->input('transaction_id');
        $responseCode = $request->input('response_code');
        $amount = $request->input('amount');
        $receivedHash = strtoupper($request->input('hash'));

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return [
                'success' => false,
                'status' => 'order_not_found'
            ];
        }

        if ($order->status === Order::STATUS_PAID) {
            return [
                'success' => false,
                'status' => 'already_processed'
            ];
        }

        if ((float) $order->total_amount !== (float) $amount) {
            return [
                'success' => false,
                'status' => 'amount_mismatch'
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Hash Verification
    |--------------------------------------------------------------------------
    */
        $payload = $request->except('hash');

        $filtered = [];

        foreach ($payload as $key => $value) {
            if ($value !== null && $value !== '') {
                $filtered[$key] = trim((string) $value);
            }
        }

        ksort($filtered);

        $salt = config('services.omniware.salt');

        $hashString = $salt;

        foreach ($filtered as $value) {
            $hashString .= '|' . $value;
        }

        $calculatedHash = strtoupper(hash('sha512', $hashString));

        if ($calculatedHash !== $receivedHash) {

            Log::warning('Hash mismatch detected', [
                'order_id' => $orderNumber,
                'transaction_id' => $transactionId,
                'payload_keys' => array_keys($filtered),
            ]);

            return [
                'success' => false,
                'status' => 'hash_failed'
            ];
        }

        if ((string) $responseCode === '0') {
            return [
                'success' => true,
                'order' => $order,
                'transaction_id' => $transactionId
            ];
        }

        return [
            'success' => false,
            'status' => 'payment_failed'
        ];
    }
}
