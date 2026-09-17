<div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-person-badge fs-5"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-dark">{{ __('Loan Overview') }}</h6>
                <div class="text-muted small" style="font-size: 0.78rem;">{{ __('Member loan and installment status') }}</div>
            </div>
        </div>
        <span class="badge {{ $row->loan->status === 'completed' ? 'bg-success-subtle text-success' : ($row->loan->status === 'overdue' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info') }} px-3 py-1 rounded-pill">
            ● {{ __(ucfirst($row->loan->status)) }}
        </span>
    </div>
    <div class="card-body p-4">
        {{-- Member profile strip --}}
        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3 border">
            <div class="rounded-circle bg-white text-primary border d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px; flex-shrink: 0;">
                {{ strtoupper(substr($row->loan->member->name, 0, 1)) }}
            </div>
            <div class="flex-grow-1 min-w-0">
                <h6 class="mb-1 fw-bold text-dark text-truncate">{{ $row->loan->member->name }}</h6>
                <div class="text-muted small d-flex flex-wrap gap-2 align-items-center" style="font-size: 0.8rem;">
                    <span><i class="bi bi-receipt me-1 text-secondary"></i>{{ $row->loan->loan_no }}</span>
                    @if ($row->loan->member->mobile)
                        <span><i class="bi bi-telephone me-1 text-secondary"></i>{{ $row->loan->member->mobile }}</span>
                    @endif
                    @if ($row->loan->area?->name)
                        <span class="badge bg-white text-secondary border"><i class="bi bi-geo-alt me-1 text-info"></i>{{ $row->loan->area->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Metrics summary row --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="p-3 rounded-3 bg-danger-subtle border border-danger-subtle">
                    <div class="text-muted small mb-1" style="font-size: 0.75rem;">{{ __('Outstanding') }}</div>
                    <div class="fs-5 fw-bold text-danger">৳{{ number_format($row->outstanding, 2) }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle">
                    <div class="text-muted small mb-1" style="font-size: 0.75rem;">{{ __('Regular Installment') }}</div>
                    <div class="fs-5 fw-bold text-primary">৳{{ number_format($row->loan->installment_amount, 2) }}</div>
                </div>
            </div>
        </div>

        {{-- Details table --}}
        <style>
            .info-icon-box {
                width: 20px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-right: 10px;
                font-size: 0.95rem;
            }
        </style>
        <div class="table-responsive">
            <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 0.85rem;">
                <tbody>
                    <tr class="border-bottom">
                        <td class="text-muted py-2"><span class="info-icon-box text-secondary"><i class="bi bi-box"></i></span>{{ __('Loan Product') }}</td>
                        <td class="text-end fw-semibold py-2">{{ __($row->loan->product->name) }}</td>
                    </tr>
                    <tr class="border-bottom">
                        <td class="text-muted py-2"><span class="info-icon-box text-primary"><i class="bi bi-calendar-check"></i></span>{{ __('Due Today') }}</td>
                        <td class="text-end fw-bold text-primary py-2">৳{{ number_format($row->due_today, 2) }}</td>
                    </tr>
                    @if ($row->overdue > 0)
                        <tr class="border-bottom">
                            <td class="text-muted py-2"><span class="info-icon-box text-danger"><i class="bi bi-clock-history"></i></span>{{ __('Overdue Balance') }}</td>
                            <td class="text-end fw-bold text-danger py-2">৳{{ number_format($row->overdue, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="border-bottom">
                        <td class="text-muted py-2"><span class="info-icon-box text-success"><i class="bi bi-check2-circle"></i></span>{{ __('Collected Today') }}</td>
                        <td class="text-end fw-bold text-success py-2">৳{{ number_format($row->collected, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2"><span class="info-icon-box text-secondary"><i class="bi bi-calculator"></i></span>{{ __('Total Payable') }}</td>
                        <td class="text-end py-2">৳{{ number_format($row->loan->total_payable, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @php
            $suggestedAmount = $row->due_today > 0 ? $row->due_today : $row->loan->installment_amount;
        @endphp
        @if ($suggestedAmount > 0)
            <div class="mt-3 pt-2">
                <button type="button" class="btn btn-sm btn-outline-primary w-100 rounded-pill py-1.5" 
                        onclick="const input = document.getElementById('loanAmountInput'); if (input) { input.value = '{{ $suggestedAmount }}'; input.focus(); }">
                    <i class="bi bi-arrow-left-circle me-2"></i>{{ __('Use Installment Amount (৳:amount)', ['amount' => number_format($suggestedAmount, 2)]) }}
                </button>
            </div>
        @endif
    </div>
</div>