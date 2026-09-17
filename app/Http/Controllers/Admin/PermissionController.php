<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorize('manage settings');

        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('permissions.index', compact('roles', 'permissions'));
    }

    public function update(Request $request)
    {
        $this->authorize('manage settings');

        $data = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.role_id' => 'required|exists:roles,id',
            'permissions.*.permission_ids' => 'nullable|array',
            'permissions.*.permission_ids.*' => 'exists:permissions,id',
        ]);

        $before = [];

        foreach ($data['permissions'] as $row) {
            $role = Role::findOrFail($row['role_id']);

            if ($role->name === 'super_admin') {
                continue;
            }

            $before[$role->name] = $role->permissions->pluck('name')->sort()->values()->all();
            $role->syncPermissions($row['permission_ids'] ?? []);
        }

        AuditLog::record('permissions.updated', null, $before, ['roles' => array_keys($before)]);

        return back()->with('success', __('Role permissions updated.'));
    }
}