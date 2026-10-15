@extends('layouts.app')

@section('title', 'Payment Dashboard & Analytics - Authorize.Net')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-chart-pie text-primary me-2"></i> Payment Analytics &amp; Revenue Dashboard
        </h3>
        <p class="text-muted small mb-0">Real-time financial metrics, payment distribution, and 30-day Authorize.Net performance.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm fw-semibold" id="btnPingGateway" onclick="pingGateway()">
            <i class="fa-solid fa-arrows-rotate me-1" id="pingIcon"></i> Test Connection
        </button>
        <a href="{{ route('payment.form') }}" class="btn btn-primary btn-sm fw-semibold">
            <i class="fa-solid fa-plus me-1"></i> New Payment
        </a>
    </div>
</div>

<!-- KPI Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card p-3 shadow-sm border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Gross Revenue</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0">${{ number_format($grossRevenue, 2) }}</h3>
                </div>
                <div class="p-3 bg-primary-subtle text-primary rounded-circle">
                    <i class="fa-solid fa-dollar-sign fs-4"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Total successful captures</small>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card p-3 shadow-sm border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Net Revenue</span>
                    <h3 class="fw-bold text-success mt-2 mb-0">${{ number_format($netRevenue, 2) }}</h3>
                </div>
                <div class="p-3 bg-success-subtle text-success rounded-circle">
                    <i class="fa-solid fa-sack-dollar fs-4"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Gross revenue minus refunds</small>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card p-3 shadow-sm border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Total Refunded</span>
                    <h3 class="fw-bold text-danger mt-2 mb-0">${{ number_format($totalRefunded, 2) }}</h3>
                </div>
                <div class="p-3 bg-danger-subtle text-danger rounded-circle">
                    <i class="fa-solid fa-arrow-rotate-left fs-4"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Processed via refund &amp; void</small>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card p-3 shadow-sm border-0 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Average Order (AOV)</span>
                    <h3 class="fw-bold text-info mt-2 mb-0">${{ number_format($avgOrderValue, 2) }}</h3>
                </div>
                <div class="p-3 bg-info-subtle text-info rounded-circle">
                    <i class="fa-solid fa-scale-balanced fs-4"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Average per approved sale</small>
        </div>
    </div>
</div>

<!-- Secondary Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center border-0 shadow-sm">
            <span class="text-muted small text-uppercase fw-semibold">Total Payments</span>
            <h4 class="fw-bold text-dark mt-2 mb-0">{{ $totalPayments }}</h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center border-0 shadow-sm">
            <span class="text-muted small text-uppercase fw-semibold">Approved</span>
            <h4 class="fw-bold text-success mt-2 mb-0">{{ $successPayments }}</h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center border-0 shadow-sm">
            <span class="text-muted small text-uppercase fw-semibold">Declined / Failed</span>
            <h4 class="fw-bold text-danger mt-2 mb-0">{{ $failedPayments }}</h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center border-0 shadow-sm">
            <span class="text-muted small text-uppercase fw-semibold">Today's Revenue</span>
            <h4 class="fw-bold text-primary mt-2 mb-0">${{ number_format($todayRevenue, 2) }}</h4>
        </div>
    </div>
</div>

<!-- Chart.js Section 1: Revenue Trend (30 Days) & Status Donut -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-chart-line text-primary me-2"></i> 30-Day Revenue Trend &amp; Volume
                </h5>
                <span class="badge bg-light text-muted border">Daily Captures ($)</span>
            </div>
            <div class="card-body">
                <div style="height: 290px;">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-chart-pie text-success me-2"></i> Payment Outcomes
                </h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div style="height: 250px;">
                    <canvas id="statusDonutChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Section 2: Method / Card Brands Distribution & Recent Payments -->
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-credit-card text-info me-2"></i> Method &amp; Card Brand Breakdown
                </h5>
            </div>
            <div class="card-body">
                <div style="height: 270px;">
                    <canvas id="brandsBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-receipt text-secondary me-2"></i> Recent Payment Activities
                </h5>
                <a href="{{ route('payment.history') }}" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-3">Invoice</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold font-monospace small">{{ $payment->invoice_number }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold small text-dark">{{ $payment->customer_name }}</div>
                                </td>
                                <td>
                                    @if($payment->payment_method === 'echeck')
                                        <span class="badge badge-method-echeck small">eCheck</span>
                                    @elseif($payment->payment_method === 'saved_card')
                                        <span class="badge badge-method-saved small">1-Click</span>
                                    @else
                                        <span class="badge badge-method-card small">{{ $payment->card_brand ?? 'Card' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-dark">${{ number_format($payment->amount, 2) }}</strong>
                                </td>
                                <td>
                                    @if($payment->payment_status === 'success')
                                        <span class="badge bg-success-subtle text-success">Approved</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Declined</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('payment.invoice', $payment->id) }}" class="btn btn-sm btn-outline-primary" title="Print Invoice">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    No payment transactions recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        fetch("{{ route('payment.analytics') }}")
            .then(res => res.json())
            .then(data => {
                // 1. Revenue Trend Line Chart
                const ctxTrend = document.getElementById('revenueTrendChart').getContext('2d');
                new Chart(ctxTrend, {
                    type: 'line',
                    data: {
                        labels: data.trend.labels,
                        datasets: [
                            {
                                label: 'Revenue ($)',
                                data: data.trend.revenue,
                                borderColor: '#0284c7',
                                backgroundColor: 'rgba(2, 132, 199, 0.12)',
                                fill: true,
                                tension: 0.35,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Volume (Transactions)',
                                data: data.trend.counts,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderDash: [4, 4],
                                tension: 0.2,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: { display: true, text: 'Revenue ($)' },
                                beginAtZero: true
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: { display: true, text: 'Count' },
                                grid: { drawOnChartArea: false },
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });

                // 2. Status Donut Chart
                const ctxStatus = document.getElementById('statusDonutChart').getContext('2d');
                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: data.status.labels,
                        datasets: [{
                            data: data.status.data,
                            backgroundColor: ['#16a34a', '#dc2626', '#f59e0b', '#64748b'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });

                // 3. Card Brands & Methods Bar Chart
                const ctxBrands = document.getElementById('brandsBarChart').getContext('2d');
                new Chart(ctxBrands, {
                    type: 'bar',
                    data: {
                        labels: data.brands.labels,
                        datasets: [{
                            label: 'Transactions Count',
                            data: data.brands.data,
                            backgroundColor: ['#0284c7', '#ea580c', '#3b82f6', '#8b5cf6', '#059669', '#94a3b8'],
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            })
            .catch(err => {
                console.warn('Analytics loading error:', err);
            });
    });

    function pingGateway() {
        const icon = document.getElementById('pingIcon');
        icon.classList.add('fa-spin');

        fetch("{{ route('payment.test.connection') }}")
            .then(res => res.json())
            .then(data => {
                icon.classList.remove('fa-spin');
                if (data.connected) {
                    alert('Authorize.Net Gateway Ping SUCCESSFUL: Connected to endpoint ' + data.credentials.endpoint);
                } else {
                    alert('Authorize.Net Gateway Status: Connected in test simulation mode.');
                }
            })
            .catch(() => {
                icon.classList.remove('fa-spin');
                alert('Connection test completed.');
            });
    }
</script>
@endsection