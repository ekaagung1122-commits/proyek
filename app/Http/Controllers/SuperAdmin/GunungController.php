<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Gunung;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GunungController extends Controller
{
    public function index() {
        $gunungs = Gunung::latest()->get();

        return response()->json([
            'message' => 'Daftar Gunung',
            'data' => $gunungs
        ]);
    }
}
