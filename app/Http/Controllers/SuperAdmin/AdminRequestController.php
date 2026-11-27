<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\AdminRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\AdminRequestDocument;
use App\Models\ActivityLog;
use App\Models\Basecamp;

use App\Mail\RequestStatusMail;
use Illuminate\Support\Facades\Mail;   
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminRequest::with(['user', 'documents']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        $data = $query->latest()
        ->paginate(10)
        ->appends($request->query());

        return response()->json([
            'message' => 'Daftar Request Admin',
            'data' => $data
        ]);
    }

    public function show($id)
    {
        return AdminRequest::with('documents')->findOrFail($id);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $req = AdminRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return response()->json([
                'message' => 'Request sudah diproses sebelumnya',
            ], 400);
        }

        $targetUser = null;

        if (!empty($req->user_id)) {
            $targetUser = User::find($req->user_id);
        } elseif (!empty($req->email)) {
            $targetUser = User::where('email', $req->email)->first();
        }

        $roleName = !empty($req->basecamp_id) ? 'admin_basecamp' : 'admin_gunung';
        $role = Role::where('name', $roleName)->first();

        if ($targetUser && $role) {
            $targetUser->roles()->syncWithoutDetaching($role->id);
            if ($req->basecamp_id) {
                $basecamp = Basecamp::find($req->basecamp_id);
                if ($basecamp) {
                    $basecamp->update(['admin_basecamp_id' => $targetUser->id]);
                }
            }
        } else {
            return response()->json([
                'message' => 'Gagal menambah role karena data di VPS tidak lengkap!',
                'detail_error' => [
                    'apakah_user_ada_di_vps' => $targetUser ? 'ADA (ID: '.$targetUser->id.')' : 'TIDAK ADA/NULL (Email pengaju belum terdaftar sebagai akun di web)',
                    'nama_role_yang_dicari' => $roleName,
                    'apakah_role_ada_di_vps_db' => $role ? 'ADA (ID: '.$role->id.')' : 'TIDAK ADA/NULL',
                    'email_dari_tabel_request' => $req->email,
                ]
            ], 422);
        }

        $req->update([
            'status' => 'approved',
            'reason' => $request->reason,
        ]);

        if (!empty($req->email)) {
            Mail::to($req->email)->send(new RequestStatusMail($req, $targetUser));
        } elseif ($targetUser && $targetUser->email) {
            Mail::to($targetUser->email)->send(new RequestStatusMail($req, $targetUser));
        }

        logActivity('approve', 'admin_request', 'Super Admin menyetujui request admin ID ' . $req->id);

        return response()->json([
            'message' => 'Request admin berhasil disetujui',
            'data' => ['user' => $targetUser->email, 'role' => $role->name]
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

        $targetUser = User::find($req->user_id);
        
        $req->update([
            'status' => 'rejected', 
            'reason' => $request->reason
        ]);

        $req->refresh();

        Mail::to($targetUser->email)->send(new RequestStatusMail($req, $targetUser));
    
        logActivity(
            'reject',
            'admin_request',
            'Super Admin menolak request admin ID ' . $req->id
        );

        return response()->json([
            'message' => 'Request admin berhasil ditolak',
        ]);
    }
}
