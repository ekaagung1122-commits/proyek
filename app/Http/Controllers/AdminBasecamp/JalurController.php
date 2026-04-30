<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Jalur;
use App\Models\Basecamp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class JalurController extends Controller
{
    public function getOwnedBasecamp($basecampId)
    {
        return Basecamp::where('id', $basecampId)
        ->where('admin_basecamp_id', auth()->id())
        ->firstOrFail();
    }


    public function index($basecampId)
    {
        $basecamp = $this->getOwnedBasecamp($basecampId);
        $jalurs = $basecamp->jalurs()
        ->latest()
        ->paginate(10);

        return response()->json([
            'message' => 'Daftar Jalur',
            'data' => $jalurs
        ]);
    }

    public function show($basecampId, $id) 
    {
        $this->getOwnedBasecamp($basecampId);

        $jalur = Jalur::where('basecamp_id', $basecampId)
        ->where('id', $id)
        ->firstOrFail();

        return response()->json([
            'message' => 'Detail Jalur',
            'data' => $jalur
        ]);
    }

    public function store(Request $request, $basecampId)
    {
        $basecamp = $this->getOwnedBasecamp($basecampId);

        $validatedData = $request->validate([
            'nama_jalur' => 'required|string|max:255',
            'estimasi_waktu' => 'required|integer|min:1',
            'status' => 'required|in:buka,tutup',
            'deskripsi' => 'nullable|string',
        ]);

        $jalur = $basecamp->jalurs()->create($validatedData);

        return response()->json([
            'message' => 'Jalur berhasil dibuat',
            'data' => $jalur
        ], 201);
    }

    public function update(Request $request, $basecampId, $jalurId)
    {
        $basecamp = $this->getOwnedBasecamp($basecampId);
        $jalur = $basecamp->jalurs()->findOrFail($jalurId);

        $validatedData = $request->validate([
            'nama_jalur' => 'required|string|max:255',
            'estimasi_waktu' => 'required|integer|min:1',
            'status' => 'required|in:buka,tutup',
            'deskripsi' => 'nullable|string',
        ]);

        $jalur->update($validatedData);

        return response()->json([
            'message' => 'Jalur berhasil diperbarui',
            'data' => $jalur
        ]);
    }

    public function destroy($basecampId, $jalurId)
    {
        $basecamp = $this->getOwnedBasecamp($basecampId);
        $jalur = $basecamp->jalurs()->findOrFail($jalurId);

        $jalur->delete();

        return response()->json([
            'message' => 'Jalur berhasil dihapus'
        ]);
    }
}
