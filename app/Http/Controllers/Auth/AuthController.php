<?php

namespace App\Http\Controllers\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\Basecamp;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $roles = $user->roles()->pluck('name')->toArray();

        $role = 'user';

        if (in_array('super_admin', $roles)) {
            $role = 'super_admin';
        } elseif (in_array('admin_gunung', $roles)) {
            $role = 'admin_gunung';
        } elseif (in_array('admin_basecamp', $roles)) {
            $role = 'admin_basecamp';
        }

        $basecamp = null;

        if ($role === 'admin_basecamp') {
            $basecamp = Basecamp::where('admin_basecamp_id', $user->id)->first();

            if (!$basecamp) {
                return response()->json([
                    'message' => 'User belum punya basecamp'
                ], 403);
            }
        }

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'basecamp_id' => $basecamp?->id,
            ],
            'token' => $token
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed', // 'confirmed' mencocokkan password_confirmation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $userRole = Role::where('name', 'user')->first();

            if ($userRole) {
                try {
                    $user->roles()->attach($userRole->id);
                } catch (\Exception $pivotError) {
                    return response()->json([
                        'message' => 'Registrasi berhasil, tetapi gagal menempelkan role. Cek nama tabel pivot Anda.',
                        'error_pivot' => $pivotError->getMessage(),
                        'token' => $user->createToken('token')->plainTextToken
                    ], 201);
                }
            }

            $token = $user->createToken('token')->plainTextToken;

            return response()->json([
                'message' => 'Registrasi berhasil',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'user',
                    'basecamp_id' => null,
                ],
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan pada server saat menyimpan data.',
                'error_detail' => $e->getMessage() // Membocorkan detail error asli ke Flutter untuk debug
            ], 500);
        }
    }
}
