<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\ActivityLog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->filled('module'), function($query) use ($request) {
                $query->where('module', $request->module);
        })
        ->latest()
        ->paginate(10)
        ->appends($request->query());

        return response()->json([
            'message' => 'Daftar Activity Log',
            'data' => $logs
        ]);
    }
}
