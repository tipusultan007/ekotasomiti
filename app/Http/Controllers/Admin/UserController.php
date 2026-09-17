<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User, 'roles' => Role::all()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $user = User::create($data + ['password' => bcrypt($data['password'])]);
        $user->syncRoles($request->input('roles', []));

        AuditLog::record('user.created', $user, [], $user->toArray());

        return redirect()->route('users.index')->with('success', __('User created successfully.'));
    }

    public function edit(User $user)
    {
        return view('users.form', ['user' => $user, 'roles' => Role::all()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateData($request);

        $before = $user->toArray();
        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);
        $user->syncRoles($request->input('roles', []));

        AuditLog::record('user.updated', $user, $before, $user->toArray());

        return redirect()->route('users.index')->with('success', __('User updated successfully.'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        AuditLog::record('user.deleted', $user, $user->toArray(), []);
        $user->delete();

        return redirect()->route('users.index')->with('success', __('User deleted successfully.'));
    }

    protected function validateData(Request $request): array
    {
        $userId = $request->route('user');

        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $request->isMethod('post') ? 'required|string|min:6' : 'nullable|string|min:6',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
        ]);
    }
}