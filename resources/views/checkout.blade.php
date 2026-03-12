@extends('layouts.app')

@section('content')

    <div class="max-w-3xl mx-auto py-10">

        <h2 class="text-2xl font-bold mb-6">Checkout</h2>

        <div class="bg-white shadow rounded p-6">

            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Amount:</strong> ₹{{ $amount }}</p>

            <hr class="my-4">

            <button id="pay_now" class="bg-green-600 text-white px-6 py-2 rounded">
                Pay Now
            </button>

        </div>

    </div>

    <script src="https://uatpgbiz8.omniware.in/js/checkout.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            if (typeof JsecurePay === "undefined") {
                console.error("JsecurePay not loaded.");
                return;
            }

            const options = {
                api_key: "{{ $apiKey }}",
                amount: "{{ number_format($amount, 2, '.', '') }}",
                currency: "INR",
                order_id: "{{ $order->order_number }}",
                reference_id: "{{ $reference_id }}",
                return_url: "{{ config('services.omniware.return_url') }}",
                signature: "{{ $signature }}",
                gateway: {
                    endpoint: "{{ $baseUrl }}"
                },
                theme: {
                    color: "#61A465"
                },
                onSuccess: function (response) {
                    console.log("Payment Success:", response);

                    setTimeout(function () {
                        window.location.href = "/payment/return?order_id={{ $order->order_number }}";
                    }, 1000);
                },
                onFailure: function (response) {
                    console.log("Payment Failure:", response);
                },
                onError: function (error) {
                    console.log("Payment Error:", error);
                }
            };

            const jsPay = new JsecurePay(options);

            document.getElementById("pay_now").addEventListener("click", function () {
                jsPay.init();
            });

            console.log("JsecurePay initialized.");
        });
        console.log("RETURN URL:", "{{ config('services.omniware.return_url') }}");
        console.log("ORDER ID:", "{{ $order->order_number }}");
    </script>


@endsection