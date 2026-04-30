<?php

namespace App\Http\Controllers\AdminGunung;

use App\Models\AdminRequest;
use App\Models\AdminRequestDocument;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function index() {
        $req = AdminRequest::where('request_by', auth()->id())
        ->latest()
        ->paginate(10);

        return response()->json([
            'message' => 'Daftar Request Admin Gunung',
            'data' => $req
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
}
