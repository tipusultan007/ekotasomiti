<div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-person-badge fs-5"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-dark">{{ __('Account Overview') }}</h6>
                <div class="text-muted small" style="font-size: 0.78rem;">{{ __('Member and account collection status') }}</div>
            </div>
        </div>
        <span class="badge {{ $row->status === 'paid' ? 'bg-success-subtle text-success' : ($row->status === 'partial' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary') }} px-3 py-1 rounded-pill">
            ● {{ __(ucfirst($row->status)) }}
        </span>
    </div>
    <div class="card-body p-4">
        {{-- Member profile strip --}}
        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3 border">
            <div class="rounded-circle bg-white text-primary border d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px; flex-shrink: 0;">
                {{ strtoupper(substr($row->account->member->name, 0, 1)) }}
            </div>
            <div class="flex-grow-1 min-w-0">
                <h6 class="mb-1 fw-bold text-dark text-truncate">{{ $row->account->member->name }}</h6>
                <div class="text-muted small d-flex flex-wrap gap-2 align-items-center" style="font-size: 0.8rem;">
                    <span><i class="bi bi-person-vcard me-1 text-secondary"></i>{{ $row->account->account_no }}</span>
                    @if ($row->account->member->mobile)
                        <span><i class="bi bi-telephone me-1 text-secondary"></i>{{ $row->account->member->mobile }}</span>
                    @endif
                    @if ($row->account->area?->name)
                        <span class="badge bg-white text-secondary border"><i class="bi bi-geo-alt me-1 text-info"></i>{{ $row->account->area->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Metrics summary row --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="p-3 rounded-3 bg-success-subtle border border-success-subtle">
                    <div class="text-muted small mb-1" style="font-size: 0.75rem;">{{ __('Current Balance') }}</div>
                    <div class="fs-5 fw-bold text-success">৳{{ number_format($row->current_balance, 2) }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle">
                    <div class="text-muted small mb-1" style="font-size: 0.75rem;">{{ __('Expected Deposit') }}</div>
                    <div class="fs-5 fw-bold text-primary">৳{{ number_format($row->expected, 2) }}</div>
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
                        <td class="text-muted py-2"><span class="info-icon-box text-secondary"><i class="bi bi-tag"></i></span>{{ __('Savings Program') }}</td>
                        <td class="text-end fw-semibold py-2">{{ __($row->account->program->name) }}</td>
                    </tr>
                    <tr class="border-bottom">
                        <td class="text-muted py-2"><span class="info-icon-box text-secondary"><i class="bi bi-calendar-event"></i></span>{{ __('Opening Date') }}</td>
                        <td class="text-end py-2">{{ $row->account->opening_date?->format('d M Y') }}</td>
                    </tr>
                    <tr class="border-bottom">
                        <td class="text-muted py-2"><span class="info-icon-box text-success"><i class="bi bi-check2-circle"></i></span>{{ __('Collected Today') }}</td>
                        <td class="text-end fw-bold text-success py-2">৳{{ number_format($row->collected, 2) }}</td>
                    </tr>
                    @if ($row->due > 0)
                        <tr>
                            <td class="text-muted py-2"><span class="info-icon-box text-danger"><i class="bi bi-exclamation-circle"></i></span>{{ __('Due Today') }}</td>
                            <td class="text-end fw-bold text-danger py-2">৳{{ number_format($row->due, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($row->expected > 0)
            <div class="mt-3 pt-2">
                <button type="button" class="btn btn-sm btn-outline-primary w-100 rounded-pill py-1.5" 
                        onclick="const input = document.getElementById('savingsAmountInput'); if (input) { input.value = '{{ $row->expected }}'; input.focus(); }">
                    <i class="bi bi-arrow-left-circle me-2"></i>{{ __('Use Expected Amount (৳:amount)', ['amount' => number_format($row->expected, 2)]) }}
                </button>
            </div>
        @endif
    </div>
</div>