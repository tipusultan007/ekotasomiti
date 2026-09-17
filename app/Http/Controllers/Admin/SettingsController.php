<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $this->authorize('manage settings');

        $settings = Setting::all()->pluck('value', 'key');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->authorize('manage settings');

        $validated = $request->validate([
            'org_name' => 'required|string|max:255',
            'org_address' => 'nullable|string',
            'org_phone' => 'nullable|string|max:30',
            'org_email' => 'nullable|email',
            'currency' => 'nullable|string|max:10',
            'date_format' => 'nullable|string|max:30',
            'account_number_format' => 'nullable|string|max:50',
            'daily_late_fee' => 'nullable|numeric|min:0',
            'withdrawal_requires_approval' => 'boolean',
            'loan_interest_flat' => 'boolean',
        ]);

        $before = $request->except(['_token']);

        foreach ($validated as $key => $value) {
            if ($key === 'withdrawal_requires_approval') {
                $value = $request->boolean('withdrawal_requires_approval') ? '1' : '0';
            }
            Setting::set($key, (string) $value);
        }

        AuditLog::record('settings.updated', null, $before, $validated);

        return back()->with('success', __('Settings saved successfully.'));
    }
}