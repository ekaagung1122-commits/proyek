<?php

namespace App\Http\Controllers\AdminGunung;

use App\Models\Basecamp;
use App\Models\Gunung;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BasecampController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Daftar Basecamp',
            'data' => Basecamp::whereHas('gunung', function($query) {
                $query->where('created_by', auth()->id());
            })
            ->latest()
            ->paginate(10)
        ]);
    }

    public function show($id)
    {
        $basecamp = Basecamp::where('id', $id)
        ->whereHas('gunung', function($query) {
            $query->where('created_by', auth()->id());
        })
        ->firstOrFail();
        return response()->json([
            'message' => 'Detail Basecamp',
            'data' => $basecamp
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'gunung_id' => 'required|exists:gunungs,id',
            'lokasi' => 'required|string',
            'harga_tiket' => 'required|integer|min:0',
            'foto_utama' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        Gunung::where('id', $request->gunung_id)
        ->where('created_by', auth()->id())
        ->firstOrFail();

        $fotoPath = null;

        if ($request->hasFile('foto_utama')) {
            $fotoPath = $request
                ->file('foto_utama')
                ->store('basecamp', 'public');
        }

        $basecamp = Basecamp::create([
            'nama' => $request->nama,
            'gunung_id' => $request->gunung_id,
            'lokasi' => $request->lokasi,
            'harga_tiket' => $request->harga_tiket,
            'foto_utama' => $fotoPath
        ]);

        return response()->json([
            'message' => 'Basecamp berhasil dibuat',
            'data' => $basecamp
        ]);
    }

    public function update(Request $request, $id)
    {
        $basecamp = Basecamp::where('id', $id)
        ->whereHas('gunung', function($query) {
            $query->where('created_by', auth()->id());
        })
        ->firstOrFail();

        $request->validate([
            'nama' => 'sometimes|required|string',
            'gunung_id' => 'sometimes|required|exists:gunungs,id',
            'lokasi' => 'sometimes|required|string',
            'harga_tiket' => 'sometimes|required|integer',
            'kuota' => 'sometimes|required|integer',
            'foto_utama' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        $data = $request->except('foto_utama');

        if ($request->hasFile('foto_utama')) {

            if ($basecamp->foto_utama && 
            Storage::disk('public')->exists($basecamp->foto_utama)
            ) {
                Storage::disk('public')->delete($basecamp->foto_utama);
            }

            $data['foto_utama'] = $request
                ->file('foto_utama')
                ->store('basecamp', 'public');
        }

        $basecamp->update($data);

        return response()->json([
            'message' => 'Basecamp berhasil diperbarui',
            'data' => $basecamp
        ]);
    }

    public function destroy($id)
    {
        $basecamp = Basecamp::where('id', $id)
        ->whereHas('gunung', function($query) {
            $query->where('created_by', auth()->id());
        })
        ->firstOrFail();
        $basecamp->delete();

        return response()->json([
            'message' => 'Basecamp berhasil dihapus'
        ]);
    }

    public function assignAdminBasecamp(Request $request, $id)
    {
        $request->validate([
            'admin_basecamp_id' => 'required|exists:users,id',
        ]);

        $basecamp = Basecamp::where('id', $id)
        ->whereHas('gunung', function($query) {
            $query->where('created_by', auth()->id());
        })
        ->firstOrFail();

        $basecamp->update([
            'admin_basecamp_id' => $request->admin_basecamp_id
        ]);

        return response()->json([
            'message' => 'Admin Basecamp berhasil ditugaskan',
            'data' => $basecamp
        ]);
    }
}
