<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class FieldOfficerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage field officers');
    }

    public function index()
    {
        $officers = User::officers()
            ->with('areas')
            ->withCount('members')
            ->orderBy('name')
            ->paginate(20);

        return view('field-officers.index', compact('officers'));
    }

    public function create()
    {
        return view('field-officers.form', [
            'officer' => new User,
            'areas' => Area::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'is_active' => 'boolean',
            'areas' => 'nullable|array',
            'areas.*' => 'exists:areas,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $user->assignRole('field_officer');
        $user->areas()->sync($request->input('areas', []));

        AuditLog::record('field_officer.created', $user, [], $user->toArray());

        return redirect()->route('field-officers.index')->with('success', __('Field Officer created successfully.'));
    }

    public function edit(User $field_officer)
    {
        return view('field-officers.form', [
            'officer' => $field_officer,
            'areas' => Area::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $field_officer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($field_officer->id)],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'is_active' => 'boolean',
            'areas' => 'nullable|array',
            'areas.*' => 'exists:areas,id',
        ]);

        $before = $field_officer->toArray();

        $field_officer->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            ...(filled($request->input('password')) ? ['password' => Hash::make($request->input('password'))] : []),
        ]);
        $field_officer->syncRoles(['field_officer']);
        $field_officer->areas()->sync($request->input('areas', []));

        AuditLog::record('field_officer.updated', $field_officer, $before, $field_officer->toArray());

        return redirect()->route('field-officers.index')->with('success', __('Field Officer updated successfully.'));
    }

    public function destroy(User $field_officer)
    {
        if ($field_officer->members()->exists()) {
            return back()->with('error', __('Field Officer cannot be deleted because they have assigned members.'));
        }

        AuditLog::record('field_officer.deleted', $field_officer, $field_officer->toArray(), []);
        $field_officer->delete();

        return redirect()->route('field-officers.index')->with('success', __('Field Officer deleted successfully.'));
    }
}
