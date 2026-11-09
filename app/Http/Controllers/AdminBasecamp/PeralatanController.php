<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Basecamp;
use App\Models\Peralatan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PeralatanController extends Controller
{
    public function getOwnedBasecamp($basecampId)
    {
        $user = auth()->user();

        if (!$user->roles->contains('name', 'admin_basecamp')) {
            abort(403, 'Unauthorized');
        }

        return Basecamp::where('id', $basecampId)
        ->where('admin_basecamp_id', auth()->id())
        ->firstOrFail();
    }

    public function index($basecampId)
    {
       $basecamp = $this->getOwnedBasecamp($basecampId);

       return response()->json([
           'message' => 'Daftar peralatan',
           'data' => $basecamp->peralatans()
           ->latest()
           ->paginate(10)
       ]);
    }

    public function show($basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $peralatan = Peralatan::where('basecamp_id', $basecampId)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'message' => 'Detail Peralatan',
            'data' => $peralatan
        ]);
    }

    public function store(Request $request, $basecampId)
    {
        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'harga_sewa' => 'required|integer|min:0',
            'kategori' => 'required|string|max:255',
            'deskripsi' => 'required|string|max:255',
            'foto' => 'required|image|max:2048'
        ]);

        $this->getOwnedBasecamp($basecampId);

        $peralatan = Peralatan::create(array_merge($request->all(), [
            'basecamp_id' => $basecampId
        ]));

        return response()->json([
            'message' => 'Peralatan berhasil ditambahkan',
            'data' => $peralatan
        ], 201);
    }

    public function update(Request $request, $basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $peralatan = Peralatan::where('basecamp_id', $basecampId)
            ->where('id', $id)
            ->firstOrFail();
        
        $validateData = $request->validate([
            'nama_alat' => 'required|string|max:255',
            'harga_sewa' => 'required|integer|min:0',
            'kategori' => 'required|string|max:255',
            'deskripsi' => 'required|string|max:255',
            'foto' => 'required|image|max:2048'
        ]);

        $peralatan->update($validateData);

        return response()->json([
            'message' => 'Peralatan berhasil diperbarui',
            'data' => $peralatan
        ]);
    }

    public function destroy($basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $peralatan = Peralatan::where('basecamp_id', $basecampId)
            ->where('id', $id)
            ->firstOrFail();

        $peralatan->delete();

        return response()->json([
            'message' => 'Peralatan berhasil dihapus'
        ]);
    }
}
