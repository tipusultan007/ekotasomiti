<?php

namespace App\Http\Controllers\Savings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SavingsProgram;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavingsProgramController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SavingsProgram::class, 'program');
    }

    public function index()
    {
        $programs = SavingsProgram::withCount('accounts')->orderBy('code')->get();

        return view('savings.programs.index', compact('programs'));
    }

    public function create()
    {
        return view('savings.programs.form', ['program' => new SavingsProgram]);
        $funds = \App\Models\Fund::active()->orderBy('name')->get();
        return view('savings.programs.form', ['program' => new SavingsProgram, 'funds' => $funds]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $program = SavingsProgram::create($data);
        AuditLog::record('savings_program.created', $program, [], $program->toArray());

        return redirect()->route('savings.programs.index')->with('success', __('Savings program created successfully.'));
    }

    public function edit(SavingsProgram $program)
    {
        return view('savings.programs.form', compact('program'));
        $funds = \App\Models\Fund::active()->orderBy('name')->get();
        return view('savings.programs.form', compact('program', 'funds'));
    }

    public function update(Request $request, SavingsProgram $program)
    {
        $data = $this->validateData($request);

        $before = $program->toArray();
        $program->update($data);
        AuditLog::record('savings_program.updated', $program, $before, $program->toArray());

        return redirect()->route('savings.programs.index')->with('success', __('Savings program updated successfully.'));
    }

    public function destroy(SavingsProgram $program)
    {
        if ($program->accounts()->exists()) {
            return back()->with('error', __('Program cannot be deleted because it has accounts.'));
        }

        AuditLog::record('savings_program.deleted', $program, $program->toArray(), []);
        $program->delete();

        return redirect()->route('savings.programs.index')->with('success', __('Savings program deleted successfully.'));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('savings_programs', 'code')->ignore($request->route('program'))],
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly',
            'prefix' => 'required|string|max:10',
            'min_deposit' => 'nullable|numeric|min:0',
            'expected_deposit' => 'nullable|numeric|min:0',
            'max_balance' => 'nullable|numeric|min:0',
            'fund_id' => 'nullable|exists:funds,id',
            'fund_contribution' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);
    }
}