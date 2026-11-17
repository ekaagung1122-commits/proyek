<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Basecamp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BasecampController extends Controller
{
    /**
     * Basecamp milik admin basecamp yang sedang login.
     */
    private function getOwnedBasecamp()
    {
        $user = auth()->user();

        if (!$user->roles->contains('name', 'admin_basecamp')) {
            abort(403, 'Unauthorized');
        }

        return Basecamp::where('admin_basecamp_id', auth()->id())
            ->firstOrFail();
    }

    public function show()
    {
        return response()->json([
            'message' => 'Detail Basecamp',
            'data' => $this->getOwnedBasecamp(),
        ]);
    }

    /**
     * Admin basecamp hanya boleh mengubah harga tiket basecamp.
     */
    public function updateHargaTiket(Request $request)
    {
        $validated = $request->validate([
            'harga_tiket' => 'required|integer|min:0',
        ]);

        $basecamp = $this->getOwnedBasecamp();
        $basecamp->update($validated);

        return response()->json([
            'message' => 'Harga tiket berhasil diperbarui',
            'data' => $basecamp,
        ]);
    }
}
