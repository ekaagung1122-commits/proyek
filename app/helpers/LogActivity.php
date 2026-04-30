use App\Models\ActivityLog;

function logActivity($action, $module, $description) {
    ActivityLog::create([
        'user_id' => auth()->id(),
        'action' => $action,
        'module' => $module
        'description' => $description,
        'ip_address' => request()->ip(),
    ]);
}