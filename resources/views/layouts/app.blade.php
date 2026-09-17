<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Ekota Somiti'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --topbar-height: 64px;
        }
        .stat-card .icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .sidebar-backdrop {
            display: none;
            position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1035;
        }
        .sidebar.show + .sidebar-backdrop { display: block; }
        .select2-container { width: 100% !important; }
        .print-area { background: #fff; }

        /* Field Officer Mobile Bottom Nav */
        .fo-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-around;
            z-index: 1045;
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.05);
            padding: 0 4px;
        }
        .fo-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 6px 0;
            transition: color 0.15s ease;
        }
        .fo-nav-item i {
            font-size: 1.25rem;
            margin-bottom: 2px;
            transition: transform 0.15s ease;
        }
        .fo-nav-item.active {
            color: #2563eb;
        }
        .fo-nav-item.active i {
            transform: translateY(-2px);
        }
        @media (max-width: 991px) {
            body.is-field-officer .page {
                padding-bottom: 84px !important;
            }
        }
        .topbar-search {
            max-width: 440px;
            position: relative;
        }
        .topbar-search .input-group {
            transition: all 0.2s ease;
            border-radius: 50rem;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }
        .topbar-search .input-group:focus-within {
            background: #fff;
            border-color: #3b82f6;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.12);
        }
        .topbar-search .input-group-text,
        .topbar-search .form-control,
        .topbar-search .btn {
            background: transparent !important;
            border: none !important;
        }
        .topbar-search .form-control:focus {
            box-shadow: none !important;
        }
        .topbar-search .search-item {
            transition: background 0.15s ease;
            text-decoration: none;
        }
        .topbar-search .search-item:hover,
        .topbar-search .search-item.active {
            background-color: #f1f5f9;
        }
        .topbar-search #topbarSearchResults {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.16) !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            z-index: 1065;
            max-height: 420px;
            overflow-y: auto;
        }
        .topbar-search #topbarSearchResults.show {
            display: block !important;
        }
    </style>
    @stack('styles')
</head>
<body class="{{ auth()->check() && auth()->user()->isFieldOfficer() ? 'is-field-officer' : '' }}">
    @include('layouts.partials.sidebar')

    <div class="main">
        <div class="topbar d-print-none">
            <button class="btn btn-light btn-sm d-lg-none me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="d-none d-xl-flex align-items-center gap-2 text-muted small me-3 flex-shrink-0">
                <i class="bi bi-calendar3"></i>
                <span class="fw-semibold">{{ now()->translatedFormat('l, d M Y') }}</span>
            </div>

            {{-- Topbar AJAX Quick Search --}}
            <div class="topbar-search flex-grow-1 me-2 me-md-4">
                <div class="input-group input-group-sm py-0.5">
                    <span class="input-group-text text-muted ps-3">
                        <i class="bi bi-search" id="topbarSearchIcon"></i>
                        <span class="spinner-border spinner-border-sm text-primary d-none" id="topbarSearchSpinner" role="status" style="width: 0.85rem; height: 0.85rem;"></span>
                    </span>
                    <input type="text" 
                           id="topbarSearchInput" 
                           class="form-control form-control-sm ps-2" 
                           placeholder="{{ __('Search member by ID, Savings A/C, Loan A/C, mobile...') }}"
                           autocomplete="off"
                           style="font-size: 0.84rem;">
                    <button class="btn btn-sm text-muted pe-3 d-none" type="button" id="topbarSearchClear">
                        <i class="bi bi-x-circle-fill text-secondary"></i>
                    </button>
                </div>

                {{-- Floating Dropdown Results Menu --}}
                <div id="topbarSearchResults" class="p-2 w-100 mt-1 shadow-lg">
                    <!-- Populated via AJAX -->
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2 flex-shrink-0">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-translate me-1"></i>{{ app()->getLocale() === 'bn' ? 'বাংলা' : 'English' }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('language.switch', 'en') }}"><i class="bi bi-check2 me-1 {{ app()->getLocale() === 'en' ? 'text-primary' : 'invisible' }}"></i>English</a></li>
                        <li><a class="dropdown-item" href="{{ route('language.switch', 'bn') }}"><i class="bi bi-check2 me-1 {{ app()->getLocale() === 'bn' ? 'text-primary' : 'invisible' }}"></i>বাংলা</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="d-none d-sm-block text-start">
                            <span class="d-block fw-semibold lh-1">{{ auth()->user()->name }}</span>
                            <span class="d-block text-muted small lh-1 mt-1">{{ ucwords(str_replace('_', ' ', auth()->user()->roles->pluck('name')->join(', ') ?: 'User')) }}</span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="page">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </div>
    </div>

    @if (auth()->check() && auth()->user()->isFieldOfficer())
        <nav class="fo-bottom-nav d-lg-none d-print-none">
            <a href="{{ route('dashboard') }}" class="fo-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>{{ __('Home') }}</span>
            </a>
            <a href="{{ route('collection.savings.sheet', 'daily') }}" class="fo-nav-item {{ request()->routeIs('collection.savings.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i>
                <span>{{ __('Savings') }}</span>
            </a>
            <a href="{{ route('collection.loans.sheet', 'daily') }}" class="fo-nav-item {{ request()->routeIs('collection.loans.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                <span>{{ __('Loans') }}</span>
            </a>
            <a href="{{ route('members.index') }}" class="fo-nav-item {{ request()->routeIs('members.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>{{ __('Members') }}</span>
            </a>
            <a href="{{ route('collection.settlements.index') }}" class="fo-nav-item {{ request()->routeIs('collection.settlements.*') ? 'active' : '' }}">
                <i class="bi bi-bank"></i>
                <span>{{ __('Settlement') }}</span>
            </a>
        </nav>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('sidebar-backdrop')) {
                document.querySelector('.sidebar').classList.remove('show');
            }
        });
        document.querySelectorAll('select.select2').forEach(el => {
            $(el).select2({ theme: 'bootstrap-5', width: '100%' });
        });

        function syncOfficerSelect(areaSelect) {
            const form = areaSelect.closest('form');
            if (!form) return;
            const officerSelect = form.querySelector('select[name="field_officer_id"]');
            if (!officerSelect) return;
            const areaId = areaSelect.value;
            const $officer = $(officerSelect);
            if (!areaId) {
                $officer.empty().append('<option value="">{{ __('Select Officer') }}</option>').trigger('change');
                return;
            }
            fetch(`/areas/${areaId}/officers`)
                .then(r => r.json())
                .then(officers => {
                    const current = $officer.val();
                    $officer.empty().append('<option value="">{{ __('Select Officer') }}</option>');
                    officers.forEach(o => {
                        $officer.append(new Option(o.name, o.id));
                    });
                    if (officers.length === 1) {
                        $officer.val(String(officers[0].id)).trigger('change');
                    } else if (current && officers.some(o => String(o.id) === String(current))) {
                        $officer.val(String(current)).trigger('change');
                    } else {
                        $officer.trigger('change');
                    }
                });
        }

        document.querySelectorAll('select[name="area_id"]').forEach(areaSelect => {
            areaSelect.addEventListener('change', function () {
                syncOfficerSelect(this);
            });
            if (areaSelect.value) {
                syncOfficerSelect(areaSelect);
            }
        });

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;

            // Handle data-confirm dialogs first
            const message = form.getAttribute('data-confirm');
            if (message) {
                e.preventDefault();
                Swal.fire({
                    title: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Yes, proceed') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.removeAttribute('data-confirm');
                        form.dataset.submitting = 'true';
                        const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                        submitBtns.forEach(btn => { btn.disabled = true; });
                        form.submit();
                    }
                });
                return;
            }

            // Prevent double submission for POST/PUT/DELETE forms
            if (form.method && form.method.toUpperCase() !== 'GET') {
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return false;
                }
                form.dataset.submitting = 'true';
                const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitBtns.forEach(btn => {
                    btn.disabled = true;
                    if (btn.tagName === 'BUTTON') {
                        btn.dataset.originalHtml = btn.innerHTML;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ' + (btn.getAttribute('data-loading-text') || '{{ __('Processing...') }}');
                    }
                });

                // Safety timeout: re-enable after 8 seconds in case of client validation failure or navigation cancellation
                setTimeout(() => {
                    form.dataset.submitting = 'false';
                    submitBtns.forEach(btn => {
                        btn.disabled = false;
                        if (btn.dataset.originalHtml) {
                            btn.innerHTML = btn.dataset.originalHtml;
                        }
                    });
                }, 8000);
            }
        });
        // Topbar Quick Search
        (function() {
            const searchInput = document.getElementById('topbarSearchInput');
            const searchResults = document.getElementById('topbarSearchResults');
            const searchClear = document.getElementById('topbarSearchClear');
            const searchIcon = document.getElementById('topbarSearchIcon');
            const searchSpinner = document.getElementById('topbarSearchSpinner');
            const searchUrl = {!! json_encode(route('members.search', [], false)) !!};
            
            const textNoResults = {!! json_encode(__('No members found')) !!};
            const textTryDifferent = {!! json_encode(__('Try searching by another account no, member ID, or phone')) !!};
            const textOverdue = {!! json_encode(__('Overdue')) !!};
            const textResult = {!! json_encode(__('Result')) !!};
            const textResults = {!! json_encode(__('Results')) !!};

            if (!searchInput || !searchResults) return;

            let debounceTimer = null;
            let currentRequest = null;
            let activeIndex = -1;

            function showLoading() {
                searchIcon.classList.add('d-none');
                searchSpinner.classList.remove('d-none');
            }

            function hideLoading() {
                searchIcon.classList.remove('d-none');
                searchSpinner.classList.add('d-none');
            }

            function showDropdown() {
                searchResults.classList.add('show');
            }

            function hideDropdown() {
                searchResults.classList.remove('show');
                searchResults.innerHTML = '';
                activeIndex = -1;
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function renderResults(data, query) {
                if (!Array.isArray(data) || data.length === 0) {
                    searchResults.innerHTML = '<div class="text-center py-3 text-muted px-2">' +
                        '<i class="bi bi-search fs-4 text-secondary mb-1 d-block opacity-50"></i>' +
                        '<div class="fw-semibold small text-dark">' + textNoResults + '</div>' +
                        '<div class="small text-muted" style="font-size: 0.75rem;">' + textTryDifferent + '</div>' +
                        '</div>';
                    showDropdown();
                    return;
                }

                let html = '<div class="px-2 py-1 small text-muted fw-semibold border-bottom mb-1" style="font-size: 0.72rem; letter-spacing: .03em; text-transform: uppercase;">' +
                    data.length + ' ' + (data.length === 1 ? textResult : textResults) + '</div>';

                data.forEach((m, idx) => {
                    const avatar = m.photo_url 
                        ? '<img src="' + escapeHtml(m.photo_url) + '" class="rounded-circle border" style="width: 38px; height: 38px; object-fit: cover; flex-shrink: 0;">'
                        : '<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-xs" style="width: 38px; height: 38px; flex-shrink: 0; font-size: 0.95rem;">' + escapeHtml((m.name || 'M').charAt(0).toUpperCase()) + '</div>';

                    let savingsBadges = '';
                    if (m.savings_accounts && m.savings_accounts.length) {
                        m.savings_accounts.forEach(sa => {
                            savingsBadges += '<span class="badge bg-success-subtle text-success border border-success-subtle font-monospace"><i class="bi bi-wallet2 me-1"></i>' + escapeHtml(sa) + '</span>';
                        });
                    }

                    let loanBadges = '';
                    if (m.loan_accounts && m.loan_accounts.length) {
                        m.loan_accounts.forEach(la => {
                            loanBadges += '<span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace"><i class="bi bi-credit-card me-1"></i>' + escapeHtml(la) + '</span>';
                        });
                    }

                    let overdueBadge = m.has_overdue 
                        ? '<span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle me-1"></i>' + textOverdue + '</span>'
                        : '';

                    let nameBnHtml = m.name_bn ? '<div class="text-muted small text-truncate" style="font-size: 0.75rem;">' + escapeHtml(m.name_bn) + '</div>' : '';
                    let mobileHtml = m.mobile ? '<span class="text-muted me-1"><i class="bi bi-telephone text-secondary me-1"></i>' + escapeHtml(m.mobile) + '</span>' : '';
                    let areaHtml = m.area_name ? '<span class="badge bg-light text-secondary border me-1"><i class="bi bi-geo-alt me-1 text-info"></i>' + escapeHtml(m.area_name) + '</span>' : '';

                    html += '<a href="' + escapeHtml(m.url) + '" class="dropdown-item p-2 rounded-3 d-flex align-items-center gap-2 mb-1 text-wrap search-item" data-index="' + idx + '" style="font-size: 0.84rem;">' +
                        avatar +
                        '<div class="flex-grow-1 min-w-0">' +
                            '<div class="d-flex align-items-center justify-content-between gap-1">' +
                                '<span class="fw-bold text-dark text-truncate">' + escapeHtml(m.name) + '</span>' +
                                '<span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.72rem;">' + escapeHtml(m.member_no) + '</span>' +
                            '</div>' +
                            nameBnHtml +
                            '<div class="d-flex flex-wrap gap-1 mt-1 align-items-center" style="font-size: 0.72rem;">' +
                                mobileHtml +
                                areaHtml +
                                savingsBadges +
                                loanBadges +
                                overdueBadge +
                            '</div>' +
                        '</div>' +
                    '</a>';
                });

                searchResults.innerHTML = html;
                showDropdown();
            }

            function performSearch(query) {
                if (currentRequest) {
                    currentRequest.abort();
                }

                if (!query || query.trim().length === 0) {
                    hideLoading();
                    hideDropdown();
                    return;
                }

                showLoading();

                const controller = new AbortController();
                currentRequest = controller;

                fetch(searchUrl + '?q=' + encodeURIComponent(query.trim()), {
                    signal: controller.signal,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => {
                    if (!r.ok) throw new Error('Search failed with status: ' + r.status);
                    return r.json();
                })
                .then(data => {
                    hideLoading();
                    renderResults(data, query);
                })
                .catch(err => {
                    if (err.name === 'AbortError') return;
                    console.error('Search error:', err);
                    hideLoading();
                    searchResults.innerHTML = '<div class="text-center py-3 text-danger px-2 small">' + escapeHtml(err.message) + '</div>';
                    showDropdown();
                });
            }

            searchInput.addEventListener('input', function() {
                const val = this.value;
                if (val.length > 0) {
                    searchClear.classList.remove('d-none');
                } else {
                    searchClear.classList.add('d-none');
                }

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    performSearch(val);
                }, 250);
            });

            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                searchClear.classList.add('d-none');
                hideDropdown();
                searchInput.focus();
            });

            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length > 0 && searchResults.children.length > 0) {
                    showDropdown();
                }
            });

            searchInput.addEventListener('keydown', function(e) {
                const items = searchResults.querySelectorAll('.search-item');
                if (!items.length || !searchResults.classList.contains('show')) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = (activeIndex + 1) % items.length;
                    items.forEach((it, i) => it.classList.toggle('active', i === activeIndex));
                    items[activeIndex]?.scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = (activeIndex - 1 + items.length) % items.length;
                    items.forEach((it, i) => it.classList.toggle('active', i === activeIndex));
                    items[activeIndex]?.scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    if (activeIndex >= 0 && items[activeIndex]) {
                        e.preventDefault();
                        window.location.href = items[activeIndex].href;
                    }
                } else if (e.key === 'Escape') {
                    hideDropdown();
                }
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    hideDropdown();
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>