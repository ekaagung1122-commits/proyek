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
            })->latest()->paginate(10)
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
        ]);

        Gunung::where('id', $request->gunung_id)
        ->where('created_by', auth()->id())
        ->firstOrFail();

        $basecamp = Basecamp::create([
            'nama' => $request->nama,
            'gunung_id' => $request->gunung_id,
            'lokasi' => $request->lokasi,
            'harga_tiket' => $request->harga_tiket,
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
            'gunnung_id' => 'sometimes|required|exists:gunungs,id',
            'lokasi' => 'sometimes|required|string',
            'harga_tiket' => 'sometimes|required|integer',
        ]);

        $basecamp->update($request->all());

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
