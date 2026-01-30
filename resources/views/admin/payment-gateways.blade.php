<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Gateways</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; }
        .card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); }
        .card:hover { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.12); }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        @include('admin.partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-[#0a0a0a] border-b border-gray-800/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-semibold text-white">Payment Gateways</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Configure payment gateway settings</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6 space-y-4">
                @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-green-400" style="font-size: 16px;">check_circle</span>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg flex items-center gap-2 text-sm">
                    <span class="material-icons-outlined text-red-400" style="font-size: 16px;">error</span>
                    <p class="text-red-300">{{ session('error') }}</p>
                </div>
                @endif

                <!-- Info Box -->
                <div class="mb-4 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-icons-outlined text-blue-400" style="font-size: 18px;">info</span>
                        <div class="text-xs text-blue-300">
                            <p class="font-semibold mb-1">Payment Gateway Configuration:</p>
                            <p class="text-blue-300/90">Configure your payment gateways (Razorpay, Cashfree, PhonePe) to accept payments. Each gateway requires API credentials from their respective dashboards. Test mode allows testing without real transactions.</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Gateways -->
                <div class="space-y-4">
                    @foreach($gateways as $gateway)
                        <div class="card rounded-lg p-5">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-white/5 rounded-lg flex items-center justify-center p-2">
                                        @if($gateway->name === 'razorpay' && file_exists(public_path('images/payment-gateways/razorpay.png')))
                                            <img src="{{ asset('images/payment-gateways/razorpay.png') }}" alt="Razorpay" class="w-full h-full object-contain">
                                        @elseif($gateway->name === 'cashfree' && file_exists(public_path('images/payment-gateways/Cashfree.png')))
                                            <img src="{{ asset('images/payment-gateways/Cashfree.png') }}" alt="Cashfree" class="w-full h-full object-contain">
                                        @elseif($gateway->name === 'phonepe' && file_exists(public_path('images/payment-gateways/phonepe.png')))
                                            <img src="{{ asset('images/payment-gateways/phonepe.png') }}" alt="PhonePe" class="w-full h-full object-contain">
                                        @else
                                            <span class="text-2xl">
                                                @if($gateway->name === 'razorpay') 💳
                                                @elseif($gateway->name === 'cashfree') 💰
                                                @elseif($gateway->name === 'phonepe') 📱
                                                @else 💳
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-white">{{ $gateway->display_name }}</h4>
                                        <p class="text-xs text-gray-500">{{ ucfirst($gateway->name) }} Payment Gateway</p>
                                    </div>
                                </div>

                                <!-- Enable/Disable Toggle -->
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           id="gateway_{{ $gateway->id }}_enabled"
                                           class="sr-only peer gateway-toggle"
                                           data-gateway-id="{{ $gateway->id }}"
                                           {{ $gateway->is_enabled ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ms-3 text-sm font-medium toggle-label-{{ $gateway->id }}">
                                        {{ $gateway->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </label>
                            </div>

                            <!-- Configuration Form -->
                            <form method="POST" action="{{ route('admin.payment-gateways.update') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="gateway_id" value="{{ $gateway->id }}">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">API Key *</label>
                                        <input type="text"
                                               name="api_key"
                                               value="{{ old('api_key', $gateway->api_key) }}"
                                               required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm font-mono focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Enter your {{ $gateway->display_name }} API Key">
                                        <p class="mt-1 text-[10px] text-gray-500">
                                            Get from
                                            @if($gateway->name === 'razorpay')
                                                <a href="https://dashboard.razorpay.com/app/keys" target="_blank" class="text-blue-400 underline">Razorpay Dashboard</a>
                                            @elseif($gateway->name === 'cashfree')
                                                <a href="https://merchant.cashfree.com/merchants/credentials" target="_blank" class="text-blue-400 underline">Cashfree Dashboard</a>
                                            @elseif($gateway->name === 'phonepe')
                                                <a href="https://business.phonepe.com/dashboard" target="_blank" class="text-blue-400 underline">PhonePe Business Dashboard</a>
                                            @endif
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">API Secret *</label>
                                        <input type="password"
                                               name="api_secret"
                                               value="{{ old('api_secret', $gateway->api_secret) }}"
                                               required
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm font-mono focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Enter your {{ $gateway->display_name }} API Secret">
                                        <p class="mt-1 text-[10px] text-gray-500">Keep this secure and never share publicly</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Merchant ID (Optional)</label>
                                        <input type="text"
                                               name="merchant_id"
                                               value="{{ old('merchant_id', $gateway->merchant_id) }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm font-mono focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Enter Merchant ID (if required)">
                                        <p class="mt-1 text-[10px] text-gray-500">Required for some gateways</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 mb-2">Webhook Secret (Optional)</label>
                                        <input type="text"
                                               name="webhook_secret"
                                               value="{{ old('webhook_secret', $gateway->webhook_secret) }}"
                                               class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded text-white text-sm font-mono focus:outline-none focus:border-blue-500 transition-colors"
                                               placeholder="Enter Webhook Secret">
                                        <p class="mt-1 text-[10px] text-gray-500">For verifying webhook callbacks</p>
                                    </div>
                                </div>

                                <div class="flex items-center pt-2">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               name="is_test_mode"
                                               class="sr-only peer"
                                               value="1"
                                               {{ $gateway->is_test_mode ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                                        <span class="ms-3 text-sm font-medium">Test Mode</span>
                                    </label>
                                    <span class="ml-3 text-xs text-gray-500">(Enable for testing - no real transactions will be processed)</span>
                                </div>

                                @if($gateway->is_enabled && !$gateway->isConfigured())
                                    <div class="mt-3 p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg flex items-center gap-2">
                                        <span class="material-icons-outlined text-amber-400" style="font-size: 16px;">warning</span>
                                        <p class="text-amber-300 text-xs">Gateway is enabled but not configured. Please add API credentials to start accepting payments.</p>
                                    </div>
                                @elseif($gateway->is_enabled && $gateway->isConfigured())
                                    <div class="mt-3 p-3 bg-green-500/10 border border-green-500/30 rounded-lg flex items-center gap-2">
                                        <span class="material-icons-outlined text-green-400" style="font-size: 16px;">check_circle</span>
                                        <p class="text-green-300 text-xs">Gateway is configured and ready to accept payments!</p>
                                    </div>
                                @endif

                                <!-- Save Button -->
                                <div class="flex justify-end pt-3">
                                    <button type="submit"
                                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                        <span class="material-icons-outlined" style="font-size: 16px;">save</span>
                                        Save {{ $gateway->display_name }} Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </main>
    </div>

    <script>
        // Payment Gateway Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const gatewayToggles = document.querySelectorAll('.gateway-toggle');

            gatewayToggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const gatewayId = this.dataset.gatewayId;
                    const isEnabled = this.checked;
                    const labelElement = document.querySelector(`.toggle-label-${gatewayId}`);

                    // Disable toggle during request
                    this.disabled = true;

                    fetch(`/admin/payment-gateways/${gatewayId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update label text
                            if (labelElement) {
                                labelElement.textContent = isEnabled ? 'Enabled' : 'Disabled';
                            }

                            // Show success message
                            showMessage(data.message || `Gateway ${isEnabled ? 'enabled' : 'disabled'} successfully`, 'success');
                        } else {
                            // Revert toggle if failed
                            this.checked = !isEnabled;
                            showMessage(data.message || 'Failed to update gateway status', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Revert toggle on error
                        this.checked = !isEnabled;
                        showMessage('Failed to update gateway status', 'error');
                    })
                    .finally(() => {
                        // Re-enable toggle
                        this.disabled = false;
                    });
                });
            });

            // Show notification message
            function showMessage(text, type = 'success') {
                const message = document.createElement('div');
                const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
                message.className = `fixed top-4 right-4 ${bgColor} text-white px-4 py-3 rounded-lg shadow-lg z-50 text-sm flex items-center gap-2`;

                const icon = type === 'success'
                    ? '<span class="material-icons-outlined" style="font-size: 18px;">check_circle</span>'
                    : '<span class="material-icons-outlined" style="font-size: 18px;">error</span>';

                message.innerHTML = icon + '<span>' + text + '</span>';
                document.body.appendChild(message);

                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.3s';
                    setTimeout(() => message.remove(), 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>
