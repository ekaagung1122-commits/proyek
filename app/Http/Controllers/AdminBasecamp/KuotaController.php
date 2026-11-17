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

        // Jangan reset kuota_terpakai bila tanggal sudah punya data.
        // Sebelumnya updateOrCreate selalu menulis kuota_terpakai = 0
        // sehingga jumlah pendaki yang sudah memesan bisa hilang.
        $kuota = BasecampKuota::firstOrNew([
            'basecamp_id' => $basecampId,
            'tanggal' => $request->tanggal,
        ]);

        $sudahAda = $kuota->exists;

        $kuota->kuota = $request->kuota;

        if (!$sudahAda) {
            $kuota->kuota_terpakai = 0;
        }

        $kuota->save();

        return response()->json([
            'message' => $sudahAda
                ? 'Kuota tanggal ini berhasil diperbarui'
                : 'Kuota berhasil disimpan',
            'data' => $kuota
        ], $sudahAda ? 200 : 201);
    }

    public function update(Request $request, $basecampId, $id)
    {
        $this->getOwnedBasecamp($basecampId);

        $kuota = BasecampKuota::where('basecamp_id', $basecampId)
            ->where('id', $id)
            ->firstOrFail();

        $validateData = $request->validate([
            'tanggal' => 'required|date',
            'kuota' => 'required|integer|min:0'
        ]);

        if ($validateData['kuota'] < $kuota->kuota_terpakai) {
            return response()->json([
                'message' => 'Kuota tidak boleh lebih kecil dari jumlah yang sudah terpakai ('
                    . $kuota->kuota_terpakai . ').'
            ], 422);
        }

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
