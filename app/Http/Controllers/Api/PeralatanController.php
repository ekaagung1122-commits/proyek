<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Peralatan;

class PeralatanController extends Controller
{
    //
    public function getByBasecamp($basecampId){
        try{
            $peralatans = Peralatan::with('kategori')
            ->where('basecamp_id', $basecampId)
            ->where('stok', '>', 0)
            ->get();

            $grouped = $peralatans->groupBy(function($item){
                return $item->kategori->nama_kategori;
            });

            $formattedData = [];
            foreach ($grouped as $kategori => $items) {
                $formattedData[] = [
                    'category' => $kategori,
                    'items' => $items
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $formattedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data peralatan: ' . $e->getMessage()
            ], 500);
        }
    }
}
