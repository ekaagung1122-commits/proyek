<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\AdminRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\AdminRequestDocument;
use App\Models\ActivityLog;

use App\Mail\RequestStatusMail;
use Illuminate\Support\Facades\Mail;   
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function index()
    {
        $query = AdminRequest::with('user');

        if (request()->has('status')) {
            $query->where('status', request()->status);
        }

        $data = $query->latest()
        ->paginate(10)
        ->appends(request()->query());

        return response()->json([
            'message' => 'Daftar Request Admin',
            'data' => $data
        ]);
    }

    public function show($id)
    {
        return AdminRequest::with('documents')->findOrFail($id);
    }

    public function approve($id)
    {
        $req = AdminRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return response()->json([
                'message' => 'Request sudah diproses sebelumnya',
            ], 400);
        }

        $targetUser = User::findOrFail($req->user_id);

        $role = Role::where('name', $req->request_type)->firstOrFail();

        $targetUser->roles()->syncWithoutDetaching($role->id);
        $req->update([
            'status' => 'approved',
            'reason' => 'Pengajuan telah disetujui',
        ]);

        Mail::to($targetUser->email)->send(new RequestStatusMail($req, $targetUser));

        activityLog(
            'approve',
            'admin_request',
            'Super Admin menyetujui request admin ID ' . $req->id
        );

        return response()->json([
            'message' => 'Request admin berhasil disetujui',
            'data' => [
                'user' => $targetUser->email,
                'role' => $role->name,
            ]
        ]);
    }

    public function reject(Request $request, $id)
    {
        $req = AdminRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return response()->json([
                'message' => 'Request sudah diproses sebelumnya',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $targetUser = User::findOrFail($req->user_id);
        
        $req->update([
            'status' => 'rejected', 
            'reason' => $request->reason
        ]);

        Mail::to($targetUser->email)->send(new RequestStatusMail($req, $targetUser));
    
        activityLog(
            'reject',
            'admin_request',
            'Super Admin menolak request admin ID ' . $req->id
        );

        return response()->json([
            'message' => 'Request admin berhasil ditolak',
        ]);
    }
}
