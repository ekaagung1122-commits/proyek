<?php

namespace App\Http\Controllers\AdminGunung;

use App\Models\AdminRequest;
use App\Models\AdminRequestDocument;
use App\Models\Basecamp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function index() {
        $req = AdminRequest::with('user')
        ->where('request_by', auth()->id())
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
            'email' => 'required|exists:users,email',
            'basecamp_id' => 'required|exists:basecamps,id',
        ]);

        Basecamp::where('id', $request->basecamp_id)
        ->whereHas('gunung', function ($q) {
            $q->where('created_by', auth()->id());
        })
        ->firstOrFail();

        $exists = AdminRequest::where('email', $request->email)
            ->where('status', 'pending')
            ->where('request_type', 'admin_basecamp')
            ->where('basecamp_id', $request->basecamp_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Request masih pending'
            ], 400);
        }

        $data = AdminRequest::create([
            'email' => $request->email,
            'request_by' => auth()->id(),
            'request_type' => 'admin_basecamp',
            'basecamp_id' => $request->basecamp_id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Request admin basecamp berhasil dibuat',
            'data' => $data
        ]);
    }
}
