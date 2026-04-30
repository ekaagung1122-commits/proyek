<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->view('user.profile', [
            'message' => 'Profile User',
            'data' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:255',
        ]);

        $user->update($validatedData);

        return response()->json([
            'message' => 'Profile berhasil diperbarui',
            'data' => $user->fresh(),
        ]);
    }

    public function uploadFoto(Request $request)
    {
        $user = auth()->user();

        $request->validate()([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
        }

        $path = $request->file('foto')->store('profiles', 'public');

        $user->update([
            'foto' => $path,
        ]);

        return response()->json([
            'message' => 'Foto profil berhasil diunggah',
            'data' => $user->fresh(),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $validatedData = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!\Hash::check($validatedData['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Password salah'
            ], 400);
        }

        $user->update([
            'password' => \Hash::make($validatedData['new_password']),
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah'
        ]);
    }   
}
