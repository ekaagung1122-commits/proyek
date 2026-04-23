<?php

namespace App\Http\Controllers\AdminGunung;

use App\Models\Gunung;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GunungController extends Controller
{
    public function index() {
        $gunungs = Gunung::where('created_by', auth()->id())->latest()->get();

        return response()->json([
            'message' => 'Daftar Gunung',
            'data' => $gunungs
        ]);
    }

    public function show($id) {
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
        ]);

        $gunung = Gunung::create([
            'nama' => $request->nama,
            'lokasi' => $request->lokasi,
            'ketinggian' => $request->ketinggian,
            'deskipsi' => $request->deskipsi,
            'foto_utama' => $request->foto_utama,
            'created_by' => auth()->id(),
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
        ]);

        $gunung->update($request->only([
            'nama', 
            'lokasi', 
            'ketinggian', 
            'deskipsi', 
            'foto_utama',
            'status'
        ]));

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
            'foto' => 'required|string',
        ]);

        $gunung = Gunung::where('id', $id)
        ->where('created_by', auth()->id())
        ->firstOrFail();

        if (!$gunung) {
            return response()->json([
                'message' => 'Gunung tidak ditemukan atau tidak dimiliki oleh admin ini'
            ], 404);
        }

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
