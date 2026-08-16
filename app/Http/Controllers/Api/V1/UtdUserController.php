<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use App\Tik\Services\BackgroundService;
use Illuminate\Support\Facades\Validator;
use App\Tik\Services\RequestBackgroundImagService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class UtdUserController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->page;
        $perPage = $request->per_page;
        $users = Admin::with("roles")->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(1, '', $users);
    }


    public function store(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'username' => 'required|string|max:255|unique:admin_users',
                'name' => 'required|string|max:255',
                // 'avatar' => 'image|nullable', 
                'password' => 'required|string',
                'roles' => 'required',
                // 'roles.*' => 'exists:admin_roles,id',
                // 'permissions' => 'required|array',
                // 'permissions.*' => 'exists:admin_permissions,id',
            ]);

            $validatedData['password'] = Hash::make($validatedData['password']);

            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validatedData['avatar'] = $avatarPath;
            }
            $user = Admin::create($validatedData);
            $user->roles()->attach(json_decode($validatedData['roles']));
            \App\Models\Admin::forgetCachedPermissionsFor($user->id);
            // $user->permissions()->attach($validatedData['permissions']);

            return response()->json(['message' => 'User created successfully.', 'user' => $user], 201);
        } catch (\Throwable $th) {
            return Common::apiResponse(0, $th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $user = Admin::findOrFail($id);

            $validatedData = $request->validate([
                'username' => "required|string|max:255|unique:admin_users,username,{$user->id}",
                'name' => 'required|string|max:255',
                // 'avatar' => 'image|nullable',
                'password' => 'nullable|string',
                'roles' => 'required',
                // 'roles.*' => 'exists:admin_roles,id',
                // 'permissions' => 'required|array',
                // 'permissions.*' => 'exists:admin_permissions,id',
            ]);

            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            } else {
                unset($validatedData['password']);
            }

            $user->update($validatedData);

            $user->roles()->sync(json_decode($validatedData['roles']));
            \App\Models\Admin::forgetCachedPermissionsFor($user->id);
            // $user->permissions()->sync($validatedData['permissions']);

            return response()->json(['message' => 'User updated successfully.', 'user' => $user], 200);
        } catch (\Throwable $th) {
            return Common::apiResponse(0, $th->getMessage());
        }
    }

    public function destroy($id)
    {
        $user = Admin::findOrFail($id);
        // if ($user->avatar) {
        //     Storage::disk('public')->delete($user->avatar); 
        // }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.'], 200);
    }

    public function show($id)
    {
        $user = Admin::with("roles")->findOrFail($id);
        return Common::apiResponse(1, '', $user);
    }
}
