<?php

namespace App\Http\Controllers\User;

use App\Models\Gunung;
use App\Models\GunungGaleri;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class GunungController extends Controller
{
    public function index(Request $request)
    {
        $query = Gunung::with('galeris');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('lokasi')) {
            $query->where('lokasi', 'like', '%' . $request->lokasi . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tinggi')) {
            $query->orderBy('ketinggian', 'desc');
        } else if ($request->filled('rendah')) {
            $query->orderBy('ketinggian', 'asc' );
        } else {
            $query->latest();
        }

        return response()->json([
            'message' => 'Daftar Gunung',
            'data' => $query->paginate(10)->appends($request->query())
        ]);
    }

    public function show($id)
    {
        $gunung = Gunung::with('galeris')
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->findOrFail($id);
        return response()->json([
            'message' => 'Detail Gunung',
            'data' => $gunung
        ]);
    }
}
