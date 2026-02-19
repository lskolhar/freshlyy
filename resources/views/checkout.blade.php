@extends('layouts.app')

@section('content')

<div class="max-w-3xl mx-auto py-10">

    <h2 class="text-2xl font-bold mb-6">Checkout</h2>

    <div class="bg-white shadow rounded p-6">

        <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
        <p><strong>Amount:</strong> ₹{{ $amount }}</p>

        <hr class="my-4">

        <button id="pay_now"
            class="bg-green-600 text-white px-6 py-2 rounded">
            Pay Now
        </button>

    </div>

</div>
<script>
    const checkoutConfig = {
        api_key: "{{ $apiKey }}",
        signature: "{{ $signature }}",
        amount: "{{ $amount }}",
        base_url: "{{ $baseUrl }}",
        order_number: "{{ $order->order_number }}"
    };

    console.log("Checkout Config:", checkoutConfig);
</script>

<script src="https://uatpgbiz8.omniware.in/js/checkout.js"></script>

<script>
    console.log("Omniware script loaded:", typeof JsecurePay !== 'undefined');
</script>


@endsection
