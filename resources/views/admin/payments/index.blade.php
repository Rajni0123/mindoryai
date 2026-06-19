<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Management | Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "neon-blue": "#3ddcff",
                        "neon-violet": "#9d5bff",
                        "soft-grey": "#d1d1d1"
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-body text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-gray-900/30 backdrop-blur-sm border-b border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold font-display text-white">Payment Management</h2>
                        <p class="text-soft-grey/70 text-sm mt-1">Verify and manage user payments</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8">
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-500/20 border border-green-500 rounded-lg text-green-500">
        {{ session('success') }}
    </div>
    @endif

    <!-- Payments Table -->
    <div class="bg-gray-800/50 rounded-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Transaction ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Plan</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Amount</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">UPI Ref</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-white">{{ $payment->transaction_id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-white font-medium">{{ $payment->user->name }}</div>
                                <div class="text-gray-400 text-sm">{{ $payment->user->email }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-white">
                            {{ $payment->plan->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-white font-semibold">
                            ₹{{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            @if($payment->upi_transaction_id)
                                <span class="font-mono text-sm text-gray-300">{{ $payment->upi_transaction_id }}</span>
                            @else
                                <span class="text-gray-500 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($payment->status === 'completed')
                                <span class="px-3 py-1 bg-green-500/20 text-green-500 rounded-full text-xs font-semibold">Completed</span>
                            @elseif($payment->status === 'verifying')
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-500 rounded-full text-xs font-semibold">Verifying</span>
                            @elseif($payment->status === 'failed')
                                <span class="px-3 py-1 bg-red-500/20 text-red-500 rounded-full text-xs font-semibold">Failed</span>
                            @else
                                <span class="px-3 py-1 bg-gray-500/20 text-gray-400 rounded-full text-xs font-semibold">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-400 text-sm">
                            {{ $payment->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($payment->status === 'verifying')
                            <div class="flex gap-2">
                                <form action="{{ route('admin.payments.confirm', $payment->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm transition-colors">
                                        Confirm
                                    </button>
                                </form>
                                <form action="{{ route('admin.payments.reject', $payment->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm transition-colors">
                                        Reject
                                    </button>
                                </form>
                            </div>
                            @else
                                <span class="text-gray-500 text-sm">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            No payments found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($payments->hasPages())
    <div class="mt-6">
        {{ $payments->links() }}
    </div>
    @endif
</div>
        </main>
    </div>
</body>
</html>
