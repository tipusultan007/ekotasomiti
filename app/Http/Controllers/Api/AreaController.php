<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $officer = $request->user();

        $areas = $officer->areas()
            ->where('areas.status', 'active')
            ->select('areas.id', 'areas.code', 'areas.name')
            ->orderBy('areas.name')
            ->get();

        return response()->json([
            'areas' => $areas,
        ]);
    }
}
