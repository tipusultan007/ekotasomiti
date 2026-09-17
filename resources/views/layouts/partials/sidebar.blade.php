<aside class="sidebar" id="sidebar">
    <div class="brand">
        <i class="bi bi-bank2"></i>
        <span class="text-truncate">{{ \App\Models\Setting::get('org_name', config('app.name')) }}</span>
    </div>
    <nav class="nav flex-column mt-2 pb-4">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 me-2"></i>{{ __('Dashboard') }}
        </a>

        {{-- Core Operations --}}
        <div class="nav-section">{{ __('Operations') }}</div>

        @canany(['manage members', 'view members'])
            <a href="{{ route('members.index') }}" class="nav-link {{ request()->routeIs('members.*') ? 'active' : '' }}">
                <i class="bi bi-people me-2"></i>{{ __('Members') }}
            </a>
            @can('manage members')
                <a href="{{ route('onboarding.create') }}" class="nav-link {{ request()->routeIs('onboarding.*') ? 'active' : '' }}">
                    <i class="bi bi-person-plus me-2"></i>{{ __('New Onboarding') }}
                </a>
            @endcan
        @endcanany

        @canany(['manage savings accounts', 'make deposits', 'manage withdrawals'])
            <a href="{{ route('savings.accounts.index') }}" class="nav-link {{ request()->routeIs('savings.accounts.*', 'savings.deposits.*', 'savings.withdrawals.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2 me-2"></i>{{ __('Savings Accounts') }}
            </a>
        @endcanany

        @canany(['manage loans', 'manage loan applications', 'approve loans', 'disburse loans', 'collect repayments'])
            <a href="{{ route('loans.index') }}" class="nav-link {{ request()->routeIs('loans.index', 'loans.show', 'loans.repay', 'loans.schedule', 'loans.applications.*', 'loans.repayments.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card me-2"></i>{{ __('Loan Accounts') }}
            </a>
        @endcanany

        @can('make deposits')
            <a href="{{ route('collection.savings.sheet', 'daily') }}" class="nav-link {{ request()->routeIs('collection.savings.*') ? 'active' : '' }}">
                <i class="bi bi-piggy-bank me-2"></i>{{ __('Savings Collections') }}
            </a>
        @endcan

        @can('collect repayments')
            <a href="{{ route('collection.loans.sheet', 'daily') }}" class="nav-link {{ request()->routeIs('collection.loans.*') ? 'active' : '' }}">
                <i class="bi bi-journal-arrow-down me-2"></i>{{ __('Loan Collections') }}
            </a>
        @endcan

        @can('manage loans')
            <a href="{{ route('loans.overdue') }}" class="nav-link {{ request()->routeIs('loans.overdue') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle me-2 text-warning"></i>{{ __('Overdue Loans') }}
            </a>
        @endcan

        {{-- Finance & Cash --}}
        @canany(['manage settlements', 'manage cash', 'manage expenses', 'manage income'])
            <div class="nav-section">{{ __('Finance & Cash') }}</div>
            @can('manage settlements')
                <a href="{{ route('collection.settlements.index') }}" class="nav-link {{ request()->routeIs('collection.settlements.*') ? 'active' : '' }}">
                    <i class="bi bi-person-check me-2"></i>{{ __('Officer Settlement') }}
                </a>
            @endcan
            @can('manage cash')
                <a href="{{ route('cash.register') }}" class="nav-link {{ request()->routeIs('cash.register') ? 'active' : '' }}">
                    <i class="bi bi-safe me-2"></i>{{ __('Cash Register') }}
                </a>
            @endcan
            @can('manage expenses')
                <a href="{{ route('cash.expenses.index') }}" class="nav-link {{ request()->routeIs('cash.expenses.*', 'cash.expense-categories.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-up-right-circle me-2"></i>{{ __('Expenses') }}
                </a>
            @endcan
            @can('manage income')
                <a href="{{ route('cash.incomes.index') }}" class="nav-link {{ request()->routeIs('cash.incomes.*', 'cash.income-categories.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-down-left-circle me-2"></i>{{ __('Income') }}
                </a>
            @endcan
            @canany(['manage cash', 'manage savings programs', 'manage funds'])
                <a href="{{ route('funds.index') }}" class="nav-link {{ request()->routeIs('funds.index', 'funds.show', 'funds.create', 'funds.edit') ? 'active' : '' }}">
                    <i class="bi bi-piggy-bank me-2"></i>{{ __('Funds & Welfare') }}
                </a>
                <a href="{{ route('funds.disburse-form') }}" class="nav-link {{ request()->routeIs('funds.disburse-form') ? 'active' : '' }}">
                    <i class="bi bi-box-arrow-up-right me-2"></i>{{ __('Spend from Fund') }}
                </a>
            @endcanany
        @endcanany

        {{-- Reports --}}
        @can('view reports')
            <div class="nav-section">{{ __('Reports') }}</div>
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                <i class="bi bi-bar-chart me-2"></i>{{ __('Reports') }}
            </a>
            <a href="{{ route('reports.summary') }}" class="nav-link {{ request()->routeIs('reports.summary*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data me-2"></i>{{ __('Financial Summary') }}
            </a>
            <a href="{{ route('reports.sheet') }}" class="nav-link {{ request()->routeIs('reports.sheet*') ? 'active' : '' }}">
                <i class="bi bi-calendar3 me-2"></i>{{ __('Monthly Sheet') }}
            </a>
        @endcan

        {{-- Administration & Configuration --}}
        @canany(['manage areas', 'manage field officers', 'manage savings programs', 'manage loan products', 'manage settings', 'manage users', 'manage bank accounts', 'view audit logs'])
            <div class="nav-section">{{ __('Administration') }}</div>
            @can('manage areas')
                <a href="{{ route('areas.index') }}" class="nav-link {{ request()->routeIs('areas.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt me-2"></i>{{ __('Areas') }}
                </a>
            @endcan
            @can('manage field officers')
                <a href="{{ route('field-officers.index') }}" class="nav-link {{ request()->routeIs('field-officers.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge me-2"></i>{{ __('Field Officers') }}
                </a>
            @endcan
            @can('manage savings programs')
                <a href="{{ route('savings.programs.index') }}" class="nav-link {{ request()->routeIs('savings.programs.*') ? 'active' : '' }}">
                    <i class="bi bi-collection me-2"></i>{{ __('Savings Programs') }}
                </a>
            @endcan
            @can('manage loan products')
                <a href="{{ route('loans.products.index') }}" class="nav-link {{ request()->routeIs('loans.products.*') ? 'active' : '' }}">
                    <i class="bi bi-briefcase me-2"></i>{{ __('Loan Products') }}
                </a>
            @endcan
            @can('manage bank accounts')
                <a href="{{ route('bank-accounts.index') }}" class="nav-link {{ request()->routeIs('bank-accounts.*') ? 'active' : '' }}">
                    <i class="bi bi-bank me-2"></i>{{ __('Bank Accounts') }}
                </a>
            @endcan
            @can('manage users')
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear me-2"></i>{{ __('Users') }}
                </a>
            @endcan
            @can('manage settings')
                <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear me-2"></i>{{ __('General Settings') }}
                </a>
                <a href="{{ route('permissions.index') }}" class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock me-2"></i>{{ __('Permissions') }}
                </a>
            @endcan
            @can('view audit logs')
                <a href="{{ route('audit.index') }}" class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text me-2"></i>{{ __('Audit Logs') }}
                </a>
            @endcan
        @endcanany
    </nav>
</aside>
<div class="sidebar-backdrop"></div>