<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>

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
    </style>
</head>
<body class="text-white">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-2xl w-full">
            <div class="bg-surface-dark/50 border border-gray-800 rounded-2xl p-8 text-center">
                @if($payment->status === 'completed')
                    <!-- Payment Successful -->
                    <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-green-500 text-5xl">check_circle</span>
                    </div>
                    <h1 class="text-3xl font-bold mb-4 text-green-500">Payment Successful!</h1>
                    <p class="text-gray-400 mb-8">Your account has been activated with the {{ $payment->plan->name }} plan.</p>

                    <div class="bg-background-dark/50 rounded-lg p-6 mb-8 text-left">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Plan</span>
                                <span class="font-semibold">{{ $payment->plan->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Amount Paid</span>
                                <span class="font-semibold">₹{{ number_format($payment->amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Transaction ID</span>
                                <span class="font-mono text-sm">{{ $payment->transaction_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Status</span>
                                <span class="text-green-500 font-semibold">Verified</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ \App\Support\ChatSubdomainUrl::appUrl() }}" class="inline-block px-8 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-colors">
                        Start Using AI
                    </a>

                @elseif($payment->status === 'verifying')
                    <!-- Payment Under Verification -->
                    <div class="w-20 h-20 bg-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-yellow-500 text-5xl">schedule</span>
                    </div>
                    <h1 class="text-3xl font-bold mb-4 text-yellow-500">Payment Under Verification</h1>
                    <p class="text-gray-400 mb-8">We've received your payment submission. Our team will verify it within 24 hours.</p>

                    <div class="bg-background-dark/50 rounded-lg p-6 mb-8 text-left">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Plan</span>
                                <span class="font-semibold">{{ $payment->plan->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Amount</span>
                                <span class="font-semibold">₹{{ number_format($payment->amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Transaction ID</span>
                                <span class="font-mono text-sm">{{ $payment->transaction_id }}</span>
                            </div>
                            @if($payment->upi_transaction_id)
                            <div class="flex justify-between">
                                <span class="text-gray-400">Payment Reference</span>
                                <span class="font-mono text-sm">{{ $payment->upi_transaction_id }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-400">Status</span>
                                <span class="text-yellow-500 font-semibold">Verifying</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-400">
                            <span class="material-symbols-outlined text-sm align-middle">info</span>
                            You will receive an email once your payment is verified and your account is activated.
                        </p>
                    </div>

                    <div class="flex gap-4 justify-center">
                        <button onclick="location.reload()" class="px-6 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            Refresh Status
                        </button>
                        <a href="{{ route('plans') }}" class="px-6 py-2 bg-surface-dark border border-gray-700 hover:bg-background-dark text-white rounded-lg transition-colors">
                            Back to Plans
                        </a>
                    </div>

                @elseif($payment->status === 'failed')
                    <!-- Payment Failed -->
                    <div class="w-20 h-20 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-red-500 text-5xl">cancel</span>
                    </div>
                    <h1 class="text-3xl font-bold mb-4 text-red-500">Payment Failed</h1>
                    <p class="text-gray-400 mb-8">We couldn't verify your payment. Please try again or contact support.</p>

                    <div class="flex gap-4 justify-center">
                        <a href="{{ route('payment.show', $payment->plan_id) }}" class="px-8 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-colors">
                            Try Again
                        </a>
                        <a href="{{ route('plans') }}" class="px-6 py-2 bg-surface-dark border border-gray-700 hover:bg-background-dark text-white rounded-lg transition-colors">
                            Back to Plans
                        </a>
                    </div>

                @else
                    <!-- Payment Pending -->
                    <div class="w-20 h-20 bg-gray-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-gray-500 text-5xl">pending</span>
                    </div>
                    <h1 class="text-3xl font-bold mb-4">Payment Pending</h1>
                    <p class="text-gray-400 mb-8">Please complete the payment to activate your account.</p>

                    <a href="{{ route('payment.show', $payment->plan_id) }}" class="inline-block px-8 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-colors">
                        Complete Payment
                    </a>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
