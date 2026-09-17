<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Area::class, 'area');
    }

    public function index()
    {
        $areas = Area::withCount('members')->orderBy('code')->paginate(20);

        return view('areas.index', compact('areas'));
    }

    public function officers(Area $area)
    {
        $officers = $area->fieldOfficers()->active()->orderBy('name')->get(['id', 'name']);

        return response()->json($officers);
    }

    public function create()
    {
        return view('areas.form', ['area' => new Area]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $area = Area::create($data);
        AuditLog::record('area.created', $area, [], $area->toArray());

        return redirect()->route('areas.index')->with('success', __('Area created successfully.'));
    }

    public function edit(Area $area)
    {
        return view('areas.form', compact('area'));
    }

    public function update(Request $request, Area $area)
    {
        $data = $this->validateData($request);

        $before = $area->toArray();
        $area->update($data);
        AuditLog::record('area.updated', $area, $before, $area->toArray());

        return redirect()->route('areas.index')->with('success', __('Area updated successfully.'));
    }

    public function destroy(Area $area)
    {
        if ($area->members()->exists() || $area->savingsAccounts()->exists() || $area->loans()->exists()) {
            return back()->with('error', __('Area cannot be deleted because it has associated records.'));
        }

        AuditLog::record('area.deleted', $area, $area->toArray(), []);
        $area->delete();

        return redirect()->route('areas.index')->with('success', __('Area deleted successfully.'));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);
    }
}