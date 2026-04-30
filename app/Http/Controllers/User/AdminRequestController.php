<?php

namespace App\Http\Controllers\User;

use App\MOdels\AdminRequest;
use App\Models\AdminRequestDocument;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{

    public function index() {
        $req = AdminRequest::where('request_by', auth()->id())->latest()->get();
    
        return response()->json([
            'message' => 'Daftar Request Admin Gunung',
            'data' => $req
        ]);
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
}
