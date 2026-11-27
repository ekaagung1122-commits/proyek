<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Basecamp;
use App\Models\Peralatan;
use App\Models\KategoriPeralatan;
use Illuminate\Support\Facades\Storage;

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
            'data' => $basecamp->peralatans()->with('kategori')->latest()->paginate(10)
        ]);
    }

    public function show($basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $peralatan = Peralatan::with('kategori')
            ->where('basecamp_id', $basecampId)
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
            'nama' => 'required|string|max:255',
            'nama_alat' => 'nullable|string|max:255',
            'harga_sewa' => 'required|integer|min:0',
            'stok' => 'nullable|integer|min:0',
            'kategori_id' => 'nullable|integer|exists:kategori_peralatans,id',
            'kategori' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
            'foto_utama' => 'nullable|image|max:2048',
        ]);

        $this->getOwnedBasecamp($basecampId);

        $namaAlat = $request->nama_alat ?? $request->nama;
        $kategoriId = $request->kategori_id;
        if (!$kategoriId && $request->filled('kategori')) {
            $kat = KategoriPeralatan::firstOrCreate(['nama_kategori' => $request->kategori]);
            $kategoriId = $kat->id;
        }
        if (!$kategoriId) {
            $kat = KategoriPeralatan::firstOrCreate(['nama_kategori' => 'Umum']);
            $kategoriId = $kat->id;
        }

        $fotoPath = null;
        if ($request->hasFile('foto_utama')) {
            $fotoPath = $request->file('foto_utama')->store('peralatan', 'public');
        }

        $peralatan = Peralatan::create([
            'basecamp_id' => $basecampId,
            'kategori_id' => $kategoriId,
            'nama_alat' => $namaAlat,
            'deskripsi' => $request->deskripsi,
            'harga_sewa' => (int) $request->harga_sewa,
            'stok' => (int) ($request->stok ?? 0),
            'foto' => $fotoPath,
        ]);

        $peralatan->load('kategori');

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

        $request->validate([
            'nama' => 'required|string|max:255',
            'nama_alat' => 'nullable|string|max:255',
            'harga_sewa' => 'required|integer|min:0',
            'stok' => 'nullable|integer|min:0',
            'kategori_id' => 'nullable|integer|exists:kategori_peralatans,id',
            'kategori' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
            'foto_utama' => 'nullable|image|max:2048',
        ]);

        $namaAlat = $request->nama_alat ?? $request->nama;
        $kategoriId = $request->kategori_id;
        if (!$kategoriId && $request->filled('kategori')) {
            $kat = KategoriPeralatan::firstOrCreate(['nama_kategori' => $request->kategori]);
            $kategoriId = $kat->id;
        }

        $data = [
            'nama_alat' => $namaAlat,
            'deskripsi' => $request->deskripsi,
            'harga_sewa' => (int) $request->harga_sewa,
            'stok' => (int) ($request->stok ?? 0),
        ];
        if ($kategoriId) {
            $data['kategori_id'] = $kategoriId;
        }

        if ($request->hasFile('foto_utama')) {
            if ($peralatan->foto && Storage::disk('public')->exists($peralatan->foto)) {
                Storage::disk('public')->delete($peralatan->foto);
            }
            $data['foto'] = $request->file('foto_utama')->store('peralatan', 'public');
        }

        $peralatan->update($data);
        $peralatan->load('kategori');

        return response()->json([
            'message' => 'Peralatan berhasil diperbarui',
            'data' => $peralatan
        ]);
    }

    public function kategoriList()
    {
        $kategoris = KategoriPeralatan::orderBy('nama_kategori')->get();
        return response()->json(['data' => $kategoris]);
    }

    public function destroy($basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $peralatan = Peralatan::where('basecamp_id', $basecampId)
            ->where('id', $id)
            ->firstOrFail();

        if ($peralatan->foto && Storage::disk('public')->exists($peralatan->foto)) {
            Storage::disk('public')->delete($peralatan->foto);
        }
        $peralatan->delete();

        return response()->json([
            'message' => 'Peralatan berhasil dihapus'
        ]);
    }
}
