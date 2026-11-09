<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Promo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    // 1. Menampilkan semua data
    public function index()
    {
        $promo = Promo::all();
        return view('promo.index', compact('promo'));
    }

    // 2. Menampilkan form tambah data
    public function create()
    {
        return view('promo.create');
    }

    // 3. Menyimpan data baru ke database
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_promo' => 'required|string|unique:promos,kode_promo',
            'jenis_potongan' => 'required|in:nominal,persen',
            'nilai_potongan' => 'required|integer',
            'kuota' => 'required|integer',
            'berlaku_sampai' => 'required|date'
        ]);

        Promo::create($validated);

        return redirect()->route('promo.index')->with('success', 'Data promo berhasil ditambahkan.');
    }

    // 4. Menampilkan detail data berdasarkan ID
    public function show(Promo $promo)
    {
        return view('promo.show', compact('promo'));
    }

    // 5. Menampilkan form edit data
    public function edit(Promo $promo)
    {
        return view('promo.edit', compact('promo'));
    }

    // 6. Mengupdate data di database
    public function update(Request $request, Promo $promo)
    {
        $validated = $request->validate([
            'kode_promo' => 'required|string|unique:promos,kode_promo,' . $promo->id,
            'jenis_potongan' => 'required|in:nominal,persen',
            'nilai_potongan' => 'required|integer',
            'kuota' => 'required|integer',
            'berlaku_sampai' => 'required|date'
        ]);

        $promo->update($validated);

        return redirect()->route('promo.index')->with('success', 'Data promo berhasil diupdate.');
    }

    // 7. Menghapus data dari database
    public function destroy(Promo $promo)
    {
        $promo->delete();

        return redirect()->route('promo.index')->with('success', 'Data promo berhasil dihapus.');
    }
}
