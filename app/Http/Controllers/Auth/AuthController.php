<?php

namespace App\Http\Controllers\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\Basecamp;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    // HANYA admin_basecamp yang wajib punya basecamp
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
}
