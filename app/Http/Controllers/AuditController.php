<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view audit logs');

        $query = AuditLog::with('user');

        if ($request->filled('search')) {
            $query->where('action', 'like', "%{$request->input('search')}%")
                ->orWhere('model_type', 'like', "%{$request->input('search')}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $logs = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('audit.index', compact('logs'));
    }
}