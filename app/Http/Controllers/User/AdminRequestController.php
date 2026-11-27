<?php

namespace App\Http\Controllers\User;

use App\Models\AdminRequest;
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
            'nama_gunung' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        $docs = [];
        // coba berbagai format key
        $f = $request->file('documents');
        if ($f) {
            $docs = is_array($f) ? $f : [$f];
        }
        if (count($docs) < 1) {
            $i = 0;
            while ($request->hasFile("documents.$i")) {
                $docs[] = $request->file("documents.$i");
                $i++;
            }
        }
        if (count($docs) < 1 && $request->hasFile('documents.0')) {
            $i = 0;
            while ($request->hasFile("documents.$i")) {
                $docs[] = $request->file("documents.$i");
                $i++;
            }
        }
        // cek allFiles untuk key yang mengandung documents
        if (count($docs) < 1) {
            foreach ($request->allFiles() as $key => $val) {
                if (strpos($key, 'documents') === 0) {
                    if (is_array($val)) {
                        foreach ($val as $v) $docs[] = $v;
                    } else {
                        $docs[] = $val;
                    }
                }
            }
        }
        if (count($docs) < 1) {
            // fallback: ambil semua file yang diupload
            $all = $request->allFiles();
            foreach ($all as $val) {
                if (is_array($val)) {
                    foreach ($val as $v) $docs[] = $v;
                } else {
                    $docs[] = $val;
                }
            }
        }
        if (count($docs) < 1) {
            return response()->json(['message' => 'Dokumen persyaratan wajib diunggah (minimal 1 file).'], 422);
        }
        foreach ($docs as $f) {
            if (!$f || !$f->isValid()) {
                return response()->json(['message' => 'File dokumen tidak valid.'], 422);
            }
            $ext = strtolower($f->getClientOriginalExtension());
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                return response()->json(['message' => 'Dokumen harus berformat PDF, JPG, atau PNG.'], 422);
            }
            if ($f->getSize() > 4096 * 1024) {
                return response()->json(['message' => 'Ukuran setiap dokumen maksimal 4MB.'], 422);
            }
        }

        $exists = AdminRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->where('request_type', 'admin_gunung')
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Request masih pending'
            ], 400);
        }

        $notesParts = [];
        if ($request->filled('nama_gunung')) {
            $notesParts[] = 'Nama Gunung: ' . $request->nama_gunung;
        }
        if ($request->filled('lokasi')) {
            $notesParts[] = 'Lokasi: ' . $request->lokasi;
        }
        if ($request->filled('deskripsi')) {
            $notesParts[] = 'Deskripsi: ' . $request->deskripsi;
        }

        $data = AdminRequest::create([
            'user_id' => auth()->id(),
            'email' => auth()->user()->email,
            'request_by' => auth()->id(),
            'request_type' => 'admin_gunung',
            'status' => 'pending',
            'notes' => count($notesParts) > 0 ? implode("\n", $notesParts) : null,
        ]);

        foreach ($docs as $file) {
            $path = $file->store('admin_documents', 'public');
            AdminRequestDocument::create([
                'admin_request_id' => $data->id,
                'document_name' => $file->getClientOriginalName(),
                'document_path' => $path,
            ]);
        }

        return response()->json([
            'message' => 'Request admin Gunung berhasil dibuat',
            'data' => $data->load('documents')
        ]);
    }

    public function history()
{
    $data = AdminRequest::where('user_id', auth()->id())
        ->with('documents')
        ->latest()
        ->get();

    return response()->json([
        'message' => 'History admin request',
        'data' => $data
    ]);
}

public function cancel($id)
{
    $request = AdminRequest::where('id', $id)
        ->where('request_by', auth()->id())
        ->first();

    if (!$request) {
        return response()->json([
            'message' => 'Pengajuan tidak ditemukan'
        ], 404);
    }

    if ($request->status !== 'pending') {
        return response()->json([
            'message' => 'Hanya pengajuan pending yang bisa dibatalkan'
        ], 400);
    }

    $request->delete();

    return response()->json([
        'message' => 'Pengajuan berhasil dibatalkan'
    ]);
}
}
