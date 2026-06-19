@extends('layouts.admin')

@section('title', 'Credit & Feature Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">💳 Credit & Feature Management</h1>
            <p class="text-muted">Manage credit system, feature flags, and user credits</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Users</h6>
                    <h2 class="mb-0">{{ number_format($totalUsers) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Credits Issued</h6>
                    <h2 class="mb-0">{{ number_format($totalCreditsIssued) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Credits Spent</h6>
                    <h2 class="mb-0">{{ number_format($totalCreditsSpent) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Unlimited Users</h6>
                    <h2 class="mb-0">{{ number_format($usersWithUnlimited) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#feature-flags">Feature Flags</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#adjust-credits">Adjust Credits</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#top-consumers">Top Consumers</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#transactions">Recent Transactions</a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Feature Flags Tab -->
        <div id="feature-flags" class="tab-pane fade show active">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">⚙️ Feature Flags</h5>
                    <small class="text-muted">Toggle features without app update</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Configuration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($featureFlags as $flag)
                                <tr>
                                    <td>
                                        <strong>{{ $flag->feature_name }}</strong><br>
                                        <small class="text-muted">{{ $flag->feature_key }}</small>
                                    </td>
                                    <td>{{ $flag->description }}</td>
                                    <td>
                                        <span class="badge bg-{{ $flag->is_enabled ? 'success' : 'secondary' }}">
                                            {{ $flag->is_enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($flag->config)
                                            <small><pre class="mb-0">{{ json_encode(json_decode($flag->config), JSON_PRETTY_PRINT) }}</pre></small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-{{ $flag->is_enabled ? 'danger' : 'success' }}"
                                            onclick="toggleFeature({{ $flag->id }}, '{{ $flag->feature_key }}', {{ $flag->is_enabled ? 'false' : 'true' }})">
                                            {{ $flag->is_enabled ? 'Disable' : 'Enable' }}
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjust Credits Tab -->
        <div id="adjust-credits" class="tab-pane fade">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">➕ Adjust User Credits</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.credits.adjust') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label">Select User *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="user_search"
                                    placeholder="Search by name, email, or mobile..."
                                    autocomplete="off">
                                <input type="hidden" name="user_id" id="user_id" required>
                                <div id="user_results" class="list-group mt-2" style="display:none;"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Amount *</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="amount"
                                    name="amount"
                                    placeholder="Positive to add, negative to deduct"
                                    required>
                                <small class="text-muted">Enter positive number to add credits, negative to deduct</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reason" class="form-label">Reason *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reason"
                                    name="reason"
                                    placeholder="e.g., Promotional bonus, Refund"
                                    maxlength="100"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="description" class="form-label">Description (Optional)</label>
                                <textarea
                                    class="form-control"
                                    id="description"
                                    name="description"
                                    rows="2"
                                    placeholder="Additional details..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Adjust Credits</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Top Consumers Tab -->
        <div id="top-consumers" class="tab-pane fade">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🔥 Top Credit Consumers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Available</th>
                                    <th>Total Earned</th>
                                    <th>Total Spent</th>
                                    <th>Unlimited</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topConsumers as $index => $consumer)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $consumer->name }}</td>
                                    <td>{{ $consumer->email }}</td>
                                    <td><strong>{{ number_format($consumer->available_credits) }}</strong></td>
                                    <td>{{ number_format($consumer->total_earned) }}</td>
                                    <td class="text-danger">{{ number_format($consumer->total_spent) }}</td>
                                    <td>
                                        @if($consumer->unlimited_mode)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.credits.user', $consumer->id) }}" class="btn btn-sm btn-info">View Details</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Tab -->
        <div id="transactions" class="tab-pane fade">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">📊 Recent Transactions</h5>
                    </div>
                    <a href="{{ route('admin.credits.export') }}" class="btn btn-sm btn-success">Export CSV</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Balance After</th>
                                    <th>Reason</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTransactions as $transaction)
                                <tr>
                                    <td>#{{ $transaction->id }}</td>
                                    <td>
                                        <small>{{ $transaction->user_name }}</small><br>
                                        <small class="text-muted">{{ $transaction->user_email }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type === 'earn' ? 'success' : ($transaction->type === 'spend' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td class="text-{{ $transaction->amount > 0 ? 'success' : 'danger' }}">
                                        {{ $transaction->amount > 0 ? '+' : '' }}{{ $transaction->amount }}
                                    </td>
                                    <td>{{ $transaction->balance_after }}</td>
                                    <td><small>{{ $transaction->reason }}</small></td>
                                    <td><small>{{ \Carbon\Carbon::parse($transaction->created_at)->diffForHumans() }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Toggle feature flag via AJAX
function toggleFeature(featureId, featureKey, enable) {
    if (!confirm(`Are you sure you want to ${enable ? 'enable' : 'disable'} this feature?`)) {
        return;
    }

    fetch(`/admin/credits/feature/${featureId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            is_enabled: enable
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update feature: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

// User search for credit adjustment
let searchTimeout;
document.getElementById('user_search')?.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value;

    if (query.length < 2) {
        document.getElementById('user_results').style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`/admin/credits/search-users?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(users => {
                const resultsDiv = document.getElementById('user_results');
                resultsDiv.innerHTML = '';

                if (users.length === 0) {
                    resultsDiv.innerHTML = '<div class="list-group-item">No users found</div>';
                } else {
                    users.forEach(user => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';
                        item.innerHTML = `<strong>${user.name}</strong><br><small>${user.email} ${user.mobile ? '| ' + user.mobile : ''}</small>`;
                        item.onclick = (e) => {
                            e.preventDefault();
                            document.getElementById('user_id').value = user.id;
                            document.getElementById('user_search').value = `${user.name} (${user.email})`;
                            resultsDiv.style.display = 'none';
                        };
                        resultsDiv.appendChild(item);
                    });
                }

                resultsDiv.style.display = 'block';
            });
    }, 300);
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#user_search') && !e.target.closest('#user_results')) {
        document.getElementById('user_results').style.display = 'none';
    }
});
</script>
@endpush
@endsection
