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

        // Jika ada basecamp_id, berarti dia daftar jadi Admin Basecamp. Jika tidak, maka Admin Gunung.
        // 1. Ambil data User dan Tentukan Role secara dinamis
        $targetUser = User::find($req->user_id);
        $roleName = !empty($req->basecamp_id) ? 'admin_basecamp' : 'admin_gunung';
        $role = Role::where('name', $roleName)->first();

        // 2. Cek Validasi Data
        if ($targetUser && $role) {
            
            $targetUser->roles()->syncWithoutDetaching($role->id);

            if ($req->basecamp_id) {
                $basecamp = Basecamp::find($req->basecamp_id);
                if ($basecamp) {
                    $basecamp->update([
                        'admin_basecamp_id' => $targetUser->id
                    ]);
                }
            }
            
        } else {
            // 🌟 KUNCI DETEKSI: Paksa Laravel mengembalikan error detail ke Frontend React kamu
            return response()->json([
                'message' => 'Gagal menambah role karena data di VPS tidak lengkap!',
                'detail_error' => [
                    'apakah_user_ada_di_vps' => $targetUser ? 'ADA (ID: '.$targetUser->id.')' : 'TIDAK ADA/NULL',
                    'nama_role_yang_dicari' => $roleName,
                    'apakah_role_ada_di_vps_db' => $role ? 'ADA (ID: '.$role->id.')' : 'TIDAK ADA/NULL',
                    'user_id_dari_tabel_request' => $req->user_id,
                ]
            ], 422); // Kita kirim status 422 agar dibaca sebagai error validasi di React
        }

        // 3. Proses update status (Hanya jalan kalau lolos pengecekan di atas)
        $req->update([
            'status' => 'approved',
            'reason' => 'Pengajuan telah disetujui',
        ]);

        // 4. Kirim Email Notifikasi
        if (!empty($req->email)) {
            Mail::to($req->email)->send(new RequestStatusMail($req, $targetUser));
        }

        logActivity(
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
