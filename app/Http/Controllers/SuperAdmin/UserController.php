<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use App\Models\Role;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Daftar User',
            'data' => User::with('roles')->paginate(10)
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $user->roles()->detach();
        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus'
        ]);
    }

    public function removeRole($id, $role) 
    {
        $user = User::findOrFail($id);
        $role = Role::where('name', $role)->first();

        if (!$role) {
            return response()->json(['message' => 'Role tidak ditemukan'], 404);
        }

        $user->roles()->detach($role->id);

        if ($user->roles()->exists()) {
            return response()->json([
                'message' => 'user tidak memiliki role apapun',
                'data' => [
                    'user' => $user->email
                ]
            ]);
        }

        return response()->json([
            'message' => 'Role berhasil dihapus dari user',
            'data' => [
                'user' => $user->email,
                'removed_role' => $role
            ]
        ]);
    }
}
