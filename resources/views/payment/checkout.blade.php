<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment - {{ $plan->name }} Plan</title>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#0df259",
                        "background-dark": "#05070a",
                        "surface-dark": "#0d1117",
                    },
                    fontFamily: {
                        "display": ["Spline Sans", "sans-serif"],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #05070a 0%, #0d1117 100%);
            min-height: 100vh;
            font-family: 'Spline Sans', sans-serif;
        }
        .glow-border {
            box-shadow: 0 0 20px rgba(13, 242, 89, 0.2);
        }
        .gateway-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .gateway-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(13, 242, 89, 0.3);
        }
        .gateway-card.selected {
            border-color: #0df259;
            background: rgba(13, 242, 89, 0.05);
        }
    </style>
</head>
<body class="text-white">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-4xl w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2">Complete Your Payment</h1>
                <p class="text-gray-400">Choose your preferred payment method</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Order Summary -->
                <div class="bg-surface-dark/50 border border-gray-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-6">Order Summary</h2>

                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Plan</span>
                            <span class="font-semibold">{{ $plan->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Duration</span>
                            <span class="font-semibold">1 Month</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Messages</span>
                            <span class="font-semibold">{{ number_format($plan->message_tokens) }}</span>
                        </div>
                        <div class="border-t border-gray-700 pt-4 flex justify-between text-xl">
                            <span class="font-bold">Total Amount</span>
                            <span class="font-bold text-primary">₹{{ number_format($plan->price, 2) }}</span>
                        </div>
                    </div>

                    <div class="bg-background-dark/50 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-400 mb-2">Transaction ID:</p>
                        <p class="font-mono text-sm text-primary">{{ $payment->transaction_id }}</p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-primary text-sm mt-0.5">check_circle</span>
                            <span class="text-sm text-gray-400">Instant activation after payment verification</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-primary text-sm mt-0.5">check_circle</span>
                            <span class="text-sm text-gray-400">Secure payment gateway</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-primary text-sm mt-0.5">check_circle</span>
                            <span class="text-sm text-gray-400">24/7 customer support</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Gateways -->
                <div class="bg-surface-dark/50 border border-gray-800 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-6">Select Payment Method</h2>

                    @if($paymentGateways->isEmpty())
                        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-6 text-center">
                            <span class="material-symbols-outlined text-red-500 text-5xl mb-3">error</span>
                            <p class="text-red-400 font-semibold">No payment methods available</p>
                            <p class="text-sm text-gray-400 mt-2">Please contact administrator</p>
                        </div>
                    @else
                        <div class="space-y-4 mb-6">
                            @foreach($paymentGateways as $gateway)
                                <div class="gateway-card bg-background-dark/50 border border-gray-700 rounded-xl p-4"
                                     data-gateway="{{ $gateway->name }}"
                                     onclick="selectGateway('{{ $gateway->name }}', '{{ $gateway->display_name }}')">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-14 h-14 bg-white/5 rounded-lg flex items-center justify-center p-2.5">
                                                @if($gateway->name === 'razorpay' && file_exists(public_path('images/payment-gateways/razorpay.png')))
                                                    <img src="{{ asset('images/payment-gateways/razorpay.png') }}" alt="Razorpay" class="w-full h-full object-contain">
                                                @elseif($gateway->name === 'cashfree' && file_exists(public_path('images/payment-gateways/Cashfree.png')))
                                                    <img src="{{ asset('images/payment-gateways/Cashfree.png') }}" alt="Cashfree" class="w-full h-full object-contain">
                                                @elseif($gateway->name === 'phonepe' && file_exists(public_path('images/payment-gateways/phonepe.webp')))
                                                    <img src="{{ asset('images/payment-gateways/phonepe.webp') }}" alt="PhonePe" class="w-full h-full object-contain">
                                                @else
                                                    @if($gateway->name === 'razorpay')
                                                        <span class="material-symbols-outlined text-blue-400">payment</span>
                                                    @elseif($gateway->name === 'cashfree')
                                                        <span class="material-symbols-outlined text-green-400">account_balance_wallet</span>
                                                    @elseif($gateway->name === 'phonepe')
                                                        <span class="material-symbols-outlined text-purple-400">phone_android</span>
                                                    @else
                                                        <span class="material-symbols-outlined text-primary">credit_card</span>
                                                    @endif
                                                @endif
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-lg">{{ $gateway->display_name }}</h3>
                                                <p class="text-xs text-gray-500">
                                                    @if($gateway->is_test_mode)
                                                        <span class="text-yellow-500">Test Mode</span>
                                                    @else
                                                        Secure payment
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <span class="material-symbols-outlined text-gray-600">chevron_right</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Payment Gateway Details Section (Hidden by default) -->
                        <div id="paymentDetails" class="hidden">
                            <div class="border-t border-gray-700 pt-6 mb-6">
                                <h3 class="font-semibold text-lg mb-4" id="selectedGatewayName"></h3>

                                <!-- Razorpay -->
                                <div id="razorpay-section" class="hidden gateway-section">
                                    <p class="text-sm text-gray-400 mb-4">Pay securely using Razorpay. You can use Credit/Debit Cards, UPI, Netbanking, and Wallets.</p>
                                    <button onclick="initiateRazorpay()" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors">
                                        Pay with Razorpay
                                    </button>
                                </div>

                                <!-- Cashfree -->
                                <div id="cashfree-section" class="hidden gateway-section">
                                    <p class="text-sm text-gray-400 mb-4">Pay securely using Cashfree. Multiple payment options available.</p>
                                    <button onclick="initiateCashfree()" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-colors">
                                        Pay with Cashfree
                                    </button>
                                </div>

                                <!-- PhonePe -->
                                <div id="phonepe-section" class="hidden gateway-section">
                                    <p class="text-sm text-gray-400 mb-4">Pay securely using PhonePe UPI.</p>
                                    <button onclick="initiatePhonePe()" class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition-colors">
                                        Pay with PhonePe
                                    </button>
                                </div>

                                <button onclick="deselectGateway()" class="w-full mt-3 py-2 text-sm text-gray-400 hover:text-primary transition-colors">
                                    Choose Different Method
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 text-center">
                        <a href="{{ route('plans') }}" class="text-sm text-gray-400 hover:text-primary transition-colors">
                            Cancel & Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedGateway = null;

        function selectGateway(gateway, displayName) {
            selectedGateway = gateway;

            // Remove selected class from all cards
            document.querySelectorAll('.gateway-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');

            // Hide all gateway sections
            document.querySelectorAll('.gateway-section').forEach(section => {
                section.classList.add('hidden');
            });

            // Show payment details
            document.getElementById('paymentDetails').classList.remove('hidden');
            document.getElementById('selectedGatewayName').textContent = displayName;

            // Show selected gateway section
            document.getElementById(gateway + '-section').classList.remove('hidden');
        }

        function deselectGateway() {
            selectedGateway = null;

            // Remove selected class from all cards
            document.querySelectorAll('.gateway-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Hide payment details
            document.getElementById('paymentDetails').classList.add('hidden');
        }

        function initiateRazorpay() {
            alert('Razorpay integration will be implemented here.\n\nPayment Amount: ₹{{ $plan->price }}\nTransaction ID: {{ $payment->transaction_id }}');
            // TODO: Implement Razorpay payment flow
            // Load Razorpay SDK and create payment order
        }

        function initiateCashfree() {
            alert('Cashfree integration will be implemented here.\n\nPayment Amount: ₹{{ $plan->price }}\nTransaction ID: {{ $payment->transaction_id }}');
            // TODO: Implement Cashfree payment flow
            // Load Cashfree SDK and create payment order
        }

        function initiatePhonePe() {
            alert('PhonePe integration will be implemented here.\n\nPayment Amount: ₹{{ $plan->price }}\nTransaction ID: {{ $payment->transaction_id }}');
            // TODO: Implement PhonePe payment flow
            // Redirect to PhonePe payment page
        }
    </script>
</body>
</html>
