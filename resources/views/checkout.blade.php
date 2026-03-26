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

                    // Wait a bit to allow gateway callback
                    setTimeout(() => {

                        fetch("/payment/check-status?order_id={{ $order->order_number }}")
                            .then(res => res.json())
                            .then(data => {

                                if (data.status === "paid") {
                                    window.location.href = "/orders";
                                } else {
                                    // ⚠️ Fallback trigger
                                    fetch("/payment/confirm", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                        },
                                        body: JSON.stringify({
                                            order_id: "{{ $order->order_number }}",
                                            transaction_id: response.transaction_id
                                        })
                                    }).then(() => {
                                        window.location.href = "/orders";
                                    });
                                }

                            });

                    }, 2000);
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