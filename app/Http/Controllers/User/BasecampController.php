<?php

namespace App\Http\Controllers\User;

use App\Models\Basecamp;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BasecampController extends Controller
{
    public function index(Request $request)
    {
        $query = Basecamp::query();

        if ($request->filled('gunung_id')) {
            $query->where('gunung_id', $request->gunung_id);
        }

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'message' => 'Daftar Basecamp',
            'data' => $query->paginate(10)->appends($request->query())
        ]);
    }

    public function show($id)
    {
        $basecamp = Basecamp::findOrFail($id);

        return response()->json([
            'message' => 'Detail Basecamp',
            'data' => $basecamp
        ]);
    }
}