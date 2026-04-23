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
        return User::with('roles')->get();
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

        return response()->json([
            'message' => 'Role berhasil dihapus dari user',
            'user' => $user->email,
            'removed_role' => $role
        ]);
    }
}
