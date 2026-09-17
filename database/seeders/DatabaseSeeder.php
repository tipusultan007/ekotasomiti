<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\BankAccount;
use App\Models\LoanProduct;
use App\Models\SavingsProgram;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedAdmin();
        $this->seedDefaults();
    }

    protected function seedPermissions(): void
    {
        $permissions = [
            'view dashboard', 'view reports',
            'manage areas', 'manage field officers',
            'manage members', 'view members',
            'manage savings programs', 'manage savings accounts', 'make deposits',
            'manage withdrawals', 'approve withdrawals',
            'manage loan products', 'manage loan applications',
            'approve loans', 'disburse loans', 'collect repayments', 'write off loans',
            'manage cash', 'manage settlements', 'close cash register',
            'manage expenses', 'manage income',
            'manage bank accounts', 'manage users', 'manage settings',
            'view audit logs', 'reverse transactions', 'manage loans',
            'view audit logs', 'reverse transactions', 'manage loans', 'manage funds',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    protected function seedRoles(): void
    {
        $roles = [
            'super_admin' => null,
            'manager' => [
                'view dashboard', 'view reports',
                'manage areas', 'manage field officers',
                'manage members', 'view members',
                'manage savings programs', 'manage savings accounts', 'make deposits',
                'manage withdrawals', 'approve withdrawals',
                'manage loan products', 'manage loan applications', 'manage loans',
                'approve loans', 'disburse loans', 'collect repayments', 'write off loans',
                'manage cash', 'manage settlements', 'close cash register',
                'manage expenses', 'manage income',
                'manage bank accounts', 'manage settings',
                'view audit logs', 'reverse transactions',
                'view audit logs', 'reverse transactions', 'manage funds',
            ],
            'field_officer' => [
                'view dashboard', 'view members', 'make deposits', 'collect repayments',
                'manage settlements', 'view reports',
            ],
            'cashier' => [
                'view dashboard', 'view members', 'make deposits',
                'manage withdrawals', 'manage cash', 'manage settlements',
                'close cash register', 'view reports',
            ],
            'accountant' => [
                'view dashboard', 'view reports', 'view members',
                'manage expenses', 'manage income', 'manage bank accounts',
                'view audit logs', 'manage cash',
                'view audit logs', 'manage cash', 'manage funds',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            if ($perms !== null) {
                $role->syncPermissions($perms);
            } else {
                $role->syncPermissions(Permission::all());
            }
        }
    }

    protected function seedAdmin(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@ekota.com'],
            [
                'name' => 'Super Admin',
                'phone' => '01700000000',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $admin->assignRole('super_admin');
    }

    protected function seedDefaults(): void
    {
        $settings = [
            'org_name' => ['Ekota Somiti', 'general'],
            'org_address' => ['Halishahar, Chattogram, Bangladesh', 'general'],
            'org_phone' => ['01700000000', 'general'],
            'org_email' => ['info@ekota.com', 'general'],
            'currency' => ['৳', 'general'],
            'date_format' => ['d-m-Y', 'general'],
            'account_number_format' => ['{prefix}-{sequence}', 'numbering'],
            'daily_late_fee' => ['0', 'loan'],
            'withdrawal_requires_approval' => ['1', 'savings'],
        ];

        foreach ($settings as $key => [$value, $group]) {
            Setting::set($key, $value, $group);
        }

        $welfareFund = \App\Models\Fund::firstOrCreate(
            ['code' => 'WF'],
            [
                'name' => 'Welfare Fund',
                'type' => 'welfare',
                'description' => 'Members welfare, emergency relief, and death benefit fund',
                'current_balance' => 0,
                'status' => 'active',
            ]
        );

        $programs = [
            ['code' => 'DS', 'name' => 'Daily Savings', 'frequency' => 'daily', 'prefix' => 'DS', 'min_deposit' => 100, 'expected_deposit' => 100],
            ['code' => 'WS', 'name' => 'Weekly Savings', 'frequency' => 'weekly', 'prefix' => 'WS', 'min_deposit' => 500, 'expected_deposit' => 500],
            ['code' => 'MS', 'name' => 'Monthly Savings', 'frequency' => 'monthly', 'prefix' => 'MS', 'min_deposit' => 1000, 'expected_deposit' => 1000],
            ['code' => 'MS', 'name' => 'Monthly Savings', 'frequency' => 'monthly', 'prefix' => 'MS', 'min_deposit' => 3000, 'expected_deposit' => 3000, 'fund_id' => $welfareFund->id, 'fund_contribution' => 50],
        ];

        foreach ($programs as $program) {
            SavingsProgram::firstOrCreate(['code' => $program['code']], $program + ['status' => 'active']);
            SavingsProgram::updateOrCreate(['code' => $program['code']], $program + ['status' => 'active']);
        }

        $loanProducts = [
            [
                'code' => 'DL', 'name' => 'Daily Loan', 'frequency' => 'daily', 'prefix' => 'DL',
                'min_amount' => 1000, 'max_amount' => 500000, 'interest_rate' => 10,
                'interest_type' => 'flat', 'processing_fee' => 0, 'insurance_fee' => 0,
                'min_term' => 10, 'max_term' => 365,
            ],
            [
                'code' => 'WL', 'name' => 'Weekly Loan', 'frequency' => 'weekly', 'prefix' => 'WL',
                'min_amount' => 5000, 'max_amount' => 1000000, 'interest_rate' => 20,
                'interest_type' => 'flat', 'processing_fee' => 0, 'insurance_fee' => 0,
                'min_term' => 4, 'max_term' => 260,
            ],
            [
                'code' => 'ML', 'name' => 'Monthly Loan', 'frequency' => 'monthly', 'prefix' => 'ML',
                'min_amount' => 10000, 'max_amount' => 5000000, 'interest_rate' => 20,
                'interest_type' => 'flat', 'processing_fee' => 0, 'insurance_fee' => 0,
                'min_term' => 3, 'max_term' => 60,
            ],
        ];

        foreach ($loanProducts as $product) {
            LoanProduct::firstOrCreate(['code' => $product['code']], $product + ['status' => 'active']);
        }

        $areas = [
            ['code' => 'CTG-01', 'name' => 'Halishahar', 'address' => 'Halishahar, Chattogram', 'status' => 'active'],
            ['code' => 'CTG-02', 'name' => 'Agrabad', 'address' => 'Agrabad, Chattogram', 'status' => 'active'],
            ['code' => 'CTG-03', 'name' => 'New Market', 'address' => 'New Market, Chattogram', 'status' => 'active'],
        ];

        foreach ($areas as $area) {
            Area::firstOrCreate(['code' => $area['code']], $area);
        }

        $allAreas = Area::all();
        $officers = [
            ['code' => 'FO-01', 'name' => 'Karim Uddin', 'mobile' => '01711111111', 'areas' => [0, 1]],
            ['code' => 'FO-02', 'name' => 'Rahim Ali', 'mobile' => '01722222222', 'areas' => [1, 2]],
            ['code' => 'FO-03', 'name' => 'Salma Begum', 'mobile' => '01733333333', 'areas' => [0, 2]],
        ];

        foreach ($officers as $def) {
            $user = User::firstOrCreate(
                ['email' => strtolower($def['code']) . '@ekota.com'],
                [
                    'name' => $def['name'],
                    'phone' => $def['mobile'],
                    'password' => 'password',
                    'is_active' => true,
                ]
            );
            $user->assignRole('field_officer');
            $user->areas()->sync(
                collect($def['areas'])->map(fn ($i) => $allAreas[$i]->id ?? null)->filter()->all()
            );
        }

        BankAccount::firstOrCreate(
            ['account_number' => '0001'],
            [
                'account_name' => 'Main Account',
                'bank_name' => 'Sonali Bank',
                'branch' => 'Agrabad',
                'opening_balance' => 0,
                'current_balance' => 0,
                'status' => 'active',
            ]
        );
    }
}