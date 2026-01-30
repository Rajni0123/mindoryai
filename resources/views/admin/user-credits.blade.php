@extends('layouts.admin')

@section('title', "User Credits - {$user->name}")

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">💳 {{ $user->name }} - Credit Details</h1>
            <p class="text-muted">{{ $user->email }}</p>
            <a href="{{ route('admin.credits.index') }}" class="btn btn-sm btn-secondary mt-2">
                ← Back to Credit Management
            </a>
        </div>
    </div>

    <!-- Credit Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Available Credits</h6>
                    <h2 class="mb-0">{{ number_format($stats['available_credits']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Earned</h6>
                    <h2 class="mb-0">{{ number_format($stats['total_earned']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Spent</h6>
                    <h2 class="mb-0">{{ number_format($stats['total_spent']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card @if($stats['unlimited_mode']) bg-info @else bg-secondary @endif text-white">
                <div class="card-body">
                    <h6 class="card-title">Unlimited Mode</h6>
                    <h4 class="mb-0">
                        @if($stats['unlimited_mode'])
                            Active
                            @if($stats['unlimited_until'])
                                <br><small>Until: {{ \Carbon\Carbon::parse($stats['unlimited_until'])->format('M d, Y') }}</small>
                            @endif
                        @else
                            Inactive
                        @endif
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Usage -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📅 Today's Usage</h5>
                    <small class="text-muted">{{ \Carbon\Carbon::today()->format('l, F j, Y') }}</small>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6 class="text-muted">Messages Sent</h6>
                            <h3 class="text-primary">{{ number_format($stats['today']['messages_sent']) }}</h3>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Images Generated</h6>
                            <h3 class="text-success">{{ number_format($stats['today']['images_generated']) }}</h3>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Credits Spent</h6>
                            <h3 class="text-warning">{{ number_format($stats['today']['credits_spent']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">⚡ Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <!-- Add Credits Button -->
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCreditsModal">
                            ➕ Add Credits
                        </button>

                        <!-- Deduct Credits Button -->
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deductCreditsModal">
                            ➖ Deduct Credits
                        </button>

                        <!-- Toggle Unlimited Mode Button -->
                        @if($stats['unlimited_mode'])
                            <button type="button" class="btn btn-warning" onclick="toggleUnlimited(false)">
                                🔒 Disable Unlimited
                            </button>
                        @else
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#unlimitedModal">
                                🔓 Enable Unlimited
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">📊 Transaction History</h5>
                        <small class="text-muted">Last 100 transactions</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Balance After</th>
                                    <th>Reason</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td><small>#{{ $transaction->id }}</small></td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type === 'earn' ? 'success' : ($transaction->type === 'spend' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td class="text-{{ $transaction->amount > 0 ? 'success' : 'danger' }}">
                                        <strong>{{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }}</strong>
                                    </td>
                                    <td>{{ number_format($transaction->balance_after) }}</td>
                                    <td><small>{{ $transaction->reason }}</small></td>
                                    <td><small class="text-muted">{{ $transaction->description ?? 'N/A' }}</small></td>
                                    <td><small class="text-muted">{{ $transaction->ip_address ?? 'N/A' }}</small></td>
                                    <td><small>{{ \Carbon\Carbon::parse($transaction->created_at)->format('M d, Y H:i') }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No transactions found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Usage History (Last 30 Days) -->
    @if($dailyUsage->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📈 Daily Usage (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Messages Sent</th>
                                    <th>Images Generated</th>
                                    <th>Credits Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyUsage as $usage)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($usage->usage_date)->format('M d, Y') }}</td>
                                    <td>{{ number_format($usage->messages_sent) }}</td>
                                    <td>{{ number_format($usage->images_generated) }}</td>
                                    <td class="text-warning">{{ number_format($usage->credits_spent) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Add Credits Modal -->
<div class="modal fade" id="addCreditsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.credits.adjust') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">➕ Add Credits</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <input type="hidden" name="amount" id="add_amount" value="">

                    <div class="mb-3">
                        <label class="form-label">Amount to Add</label>
                        <input type="number" class="form-control" id="add_amount_input" min="1" value="50" required>
                        <small class="text-muted">Enter positive number</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason *</label>
                        <input type="text" class="form-control" name="reason" placeholder="e.g., Promotional bonus" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Additional details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" onclick="document.getElementById('add_amount').value = document.getElementById('add_amount_input').value">Add Credits</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deduct Credits Modal -->
<div class="modal fade" id="deductCreditsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.credits.adjust') }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">➖ Deduct Credits</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <input type="hidden" name="amount" id="deduct_amount" value="">

                    <div class="mb-3">
                        <label class="form-label">Amount to Deduct</label>
                        <input type="number" class="form-control" id="deduct_amount_input" min="1" value="10" required>
                        <small class="text-muted">Enter positive number</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason *</label>
                        <input type="text" class="form-control" name="reason" placeholder="e.g., Policy violation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Additional details..."></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <strong>⚠️ Warning:</strong> This will deduct credits from the user's balance. Current balance: <strong>{{ number_format($stats['available_credits']) }}</strong> credits.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" onclick="document.getElementById('deduct_amount').value = -Math.abs(document.getElementById('deduct_amount_input').value)">Deduct Credits</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enable Unlimited Mode Modal -->
<div class="modal fade" id="unlimitedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.credits.unlimited') }}" method="POST">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">🔓 Enable Unlimited Mode</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <input type="hidden" name="unlimited_mode" value="1">

                    <div class="mb-3">
                        <label class="form-label">Until When?</label>
                        <input type="date" class="form-control" name="unlimited_until" min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}">
                        <small class="text-muted">Optional. Leave empty for no expiry.</small>
                    </div>

                    <div class="alert alert-info">
                        <strong>ℹ️ Info:</strong> Unlimited mode allows the user to perform actions without credit deductions. The user will not be charged until this mode expires or is disabled.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Enable Unlimited</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Toggle unlimited mode (disable via AJAX)
function toggleUnlimited(enable) {
    if (!confirm('Are you sure you want to disable unlimited mode for this user?')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.credits.unlimited") }}';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]').content;

    const userId = document.createElement('input');
    userId.type = 'hidden';
    userId.name = 'user_id';
    userId.value = '{{ $user->id }}';

    const unlimitedMode = document.createElement('input');
    unlimitedMode.type = 'hidden';
    unlimitedMode.name = 'unlimited_mode';
    unlimitedMode.value = '0';

    form.appendChild(csrf);
    form.appendChild(userId);
    form.appendChild(unlimitedMode);

    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
@endsection
