<?php

namespace App\Http\Controllers\User;

use App\Models\Gunung;
use App\Models\GunungGaleri;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class GunungController extends Controller
{
    public function index()
    {
        return Gunung::with('galeris')->get(); 
    }

    public function show($id)
    {
        $gunung = Gunung::with('galeris')->findOrFail($id);
        return response()->json($gunung);
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

    public function tambahGaleri(Request $request, $id)
    {
        $request->validate([
            'foto' => 'required|string',
        ]);

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
