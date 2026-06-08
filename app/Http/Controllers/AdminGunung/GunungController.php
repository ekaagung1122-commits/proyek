<?php

namespace App\Http\Controllers\AdminGunung;

use App\Models\Gunung;
use App\Models\GunungGaleri;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GunungController extends Controller
{
    public function index()
    {
        $gunungs = Gunung::where('created_by', auth()->id())
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar Gunung',
            'data' => $gunungs
        ]);
    }

    public function show($id)
    {
        $gunung = Gunung::where('id', $id)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        return response()->json([
            'message' => 'Detail Gunung',
            'data' => $gunung
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'lokasi' => 'required|string',
            'ketinggian' => 'required|integer',
            'deskripsi' => 'nullable|string',
            'foto_utama' => 'nullable|image|mimes:jpg,jpeg,png|max:4096'
        ]);

        $fotoPath = null;

        if ($request->hasFile('foto_utama')) {

            $fotoPath = $request
                ->file('foto_utama')
                ->store('gunung', 'public');
        }

        $gunung = Gunung::create([
            'nama' => $request->nama,
            'lokasi' => $request->lokasi,
            'ketinggian' => $request->ketinggian,
            'deskripsi' => $request->deskripsi,
            'foto_utama' => $fotoPath,
            'created_by' => auth()->id(),
            'status' => 1,
        ]);

        return response()->json([
            'message' => 'Gunung berhasil dibuat',
            'data' => $gunung
        ]);
    }

    public function update(Request $request, $id)
    {
        $gunung = Gunung::where('id', $id)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $request->validate([
            'nama' => 'sometimes|required|string',
            'lokasi' => 'sometimes|required|string',
            'ketinggian' => 'sometimes|required|integer',
            'deskripsi' => 'nullable|string',
            'status' => 'sometimes|boolean',
            'foto_utama' => 'nullable|image|mimes:jpg,jpeg,png|max:4096'
        ]);

        if ($request->filled('nama')) {
            $gunung->nama = $request->nama;
        }

        if ($request->filled('lokasi')) {
            $gunung->lokasi = $request->lokasi;
        }

        if ($request->filled('ketinggian')) {
            $gunung->ketinggian = $request->ketinggian;
        }

        if ($request->filled('deskripsi')) {
            $gunung->deskripsi = $request->deskripsi;
        }

        if ($request->has('status')) {
            $gunung->status = $request->status;
        }

        if ($request->hasFile('foto_utama')) {

            $fotoPath = $request
                ->file('foto_utama')
                ->store('gunung', 'public');

            $gunung->foto_utama = $fotoPath;
        }

        $gunung->save();

        return response()->json([
            'message' => 'Gunung berhasil diperbarui',
            'data' => $gunung
        ]);
    }

    public function destroy($id)
    {
        $gunung = Gunung::where('id', $id)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $gunung->delete();

        return response()->json([
            'message' => 'Gunung berhasil dihapus'
        ]);
    }

    public function tambahGaleri(Request $request, $id)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,jpg,png|max:4096',
        ]);

        $gunung = Gunung::where('id', $id)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $galeri = GunungGaleri::create([
            'foto' => $request->foto,
            'caption' => $request->caption,
            'gunung_id' => $id,
        ]);

        return response()->json([
            'message' => 'Galeri berhasil ditambahkan',
            'data' => $galeri
        ]);
    }
}