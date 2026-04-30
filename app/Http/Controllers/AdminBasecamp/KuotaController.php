<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Basecamp;
use App\Models\BasecampKuota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KuotaController extends Controller
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

       return response()->json([
           'message' => 'Daftar Kuota',
           'data' => $basecamp->kuotas()
           ->latest()
           ->paginate(10)
       ]);
    }

    public function show($basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $kuota = BasecampKuota::where('basecamp_id', $basecampId)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'message' => 'Detail Kuota',
            'data' => $kuota
        ]);
    }

    public function store(Request $request, $basecampId)
    {
        $request->validate([
            'tanggal' => 'required|date|after_or_equal:today',
            'kuota' => 'required|integer|min:0'
        ]);

        $this->getOwnedBasecamp($basecampId);

        $kuota = BasecampKuota::updateOrCreate(
            [
                'basecamp_id' => $basecampId, 
                'tanggal' => $request->tanggal
            ],
            [
                'kuota' => $request->kuota
            ]
        );

        return response()->json([
            'message' => 'Kuota berhasil disimpan',
            'data' => $kuota
        ], 201);
    }

    public function update(Request $request, $basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $kuota = BasecampKuota::where('basecamp_id', $basecampId)
            ->where('id', $id)
            ->firstOrFail();
        
        $validateData = $request->validate([
            'tanggal' => 'required|date|after_or_equal:today',
            'kuota' => 'required|integer|min:0'
        ]);

        $kuota->update($validateData);

        return response()->json([
            'message' => 'Kuota berhasil diperbarui',
            'data' => $kuota
        ]);
    }

    public function destroy($basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $kuota = BasecampKuota::where('basecamp_id', $basecampId)
            ->where('id', $id)
            ->firstOrFail();

        $kuota->delete();

        return response()->json([
            'message' => 'Kuota berhasil dihapus'
        ]);
    }
}
