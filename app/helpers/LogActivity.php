<?php

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

if (!function_exists('logActivity')) {

    function logActivity($action, $module, $description)
    {
        ActivityLog::create([
            'user_id' => Auth::check() ? Auth::id() : 1,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}