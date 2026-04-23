<?php

namespace App\Http\Controllers\Auth;

use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request){
       $user = User::where('email', $request->email)->first();

       if (! $user || !Hash::check($request->password, $user->password)) {
           return response()->json(['message' => 'Unauthorized'], 401);
       }

       $token = $user->createToken('token')->plainTextToken;

       return response()->json([
           'user' => $user,
           'token' => $token
       ]);
    }

    public function register(Request $request){
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $role = Role::where('name', 'user')->first();

        if (!$role) {
            return response()->json(['message' => 'Role tidak ditemukan'], 404);
        }

        $user->roles()->attach($role->id);

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request){
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }   

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);

        dd($request->user());
    }
}
