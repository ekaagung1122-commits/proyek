<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\AdminRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\AdminRequestDocument;
use App\Mail\RequestStatusMail;
use Illuminate\Support\Facades\Mail;   
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function index()
    {
        return AdminRequest::with('user')->latest()->get();
    }

    public function show($id)
    {
        return AdminRequest::with('documents')->findOrFail($id);
    }

    public function requestAdminGunung(Request $request)
    {
        $request->validate([
            'request_type' => 'required|in:admin_gunung',
            'documents.*' => 'required|file|mimes:pdf,jpg,png|max:4096',
        ]);

        $exists = AdminRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->where('request_type', 'admin_gunung')
            ->exists();

        if ($exists) {
            return response()->json
            ([
                'message' => 'Request masih pending'
            ], 400);
        }

        $data = AdminRequest::create([
            'user_id' => auth()->id(),
            'request_by' => auth()->id(),
            'request_type' => 'admin_gunung',
            'status' => 'pending',
        ]);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('admin_documents', 'public');

                AdminRequestDocument::create([
                    'admin_request_id' => $data->id,
                    'document_name' => $file->getClientOriginalName(),
                    'document_path' => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Request admin Gunung berhasil dibuat',
            'data' => $data->load('documents')
        ]);
    }

    public function requestAdminBasecamp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $exists = AdminRequest::where('user_id', $request->user_id)
            ->where('status', 'pending')
            ->where('request_type', 'admin_basecamp')
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Request masih pending'
            ], 400);
        }

        $data = AdminRequest::create([
            'user_id' => $request->user_id,
            'request_by' => auth()->id(),
            'request_type' => 'admin_basecamp',
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Request admin basecamp berhasil dibuat',
            'data' => $data
        ]);
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
        if (!$role) {
            return response()->json([
                'message' => 'Role tidak ditemukan',
            ], 404);
        }

        $targetUser->roles()->SyncWithoutDetaching($role->id);
        $req->update([
            'status' => 'approved',
            'reason' => 'Pengajuan telah disetujui',
        ]);

        Mail::to($targetUser->email)->send(new RequestStatusMail($req, $targetUser));

        return response()->json([
            'message' => 'Request admin berhasil disetujui',
            'user' => $targetUser->email,
            'role' => $role->name,
        ]);
    }

    public function reject($id)
    {
        $req = AdminRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return response()->json([
                'message' => 'Request sudah diproses sebelumnya',
            ], 400);
        }

        $requset->validate([
            'reason' => 'required|string|max:255',
        ]);

        $targetUser = User::findOrFail($req->user_id);
        
        $req->update([
            'status' => 'rejected', 
            'reason' => $request->reason
        ]);

        Mail::to($targetUser->email)->send(new RequestStatusMail($req, $targetUser));
    
        return response()->json([
            'message' => 'Request admin berhasil ditolak',
        ]);
    }
}
