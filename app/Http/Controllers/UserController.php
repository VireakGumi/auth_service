<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get a list of users (admin)",
     *     tags={"Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="Page number",
     *          required=false,
     *          @OA\Schema(type="integer", default=1)
     *      ),
     *      @OA\Parameter(
     *          name="size",
     *          in="query",
     *          description="Items per page",
     *          required=false,
     *          @OA\Schema(type="integer", default=15)
     *      ),
     *      @OA\Parameter(
     *          name="scol",
     *          in="query",
     *          description="Column to sort by",
     *          required=false,
     *          @OA\Schema(type="string", default="id", enum={"id","name"})
     *      ),
     *      @OA\Parameter(
     *          name="sdir",
     *          in="query",
     *          description="Sort direction",
     *          required=false,
     *          @OA\Schema(type="string", default="desc", enum={"asc","desc"})
     *      ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="role_ids",
     *         in="query",
     *         description="Filter by role IDs (comma-separated)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status (1 for active, 0 for inactive)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0,1})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *     )
     * )
     */
    public function index(Request $request)
    {
        $page = $request->filled('page') ? intval($request->query('page')) : 1;
        $size = $request->filled('size') ? intval($request->query('size')) : 15;
        $scol = $request->filled('scol') ? strval($request->query('scol')) : 'id';
        $sdir = $request->filled('sdir') ? strval($request->query('sdir')) : 'desc';
        $search = $request->filled('search') ? strval($request->query('search')) : '';
        $role_ids = $request->filled('role_ids') ? explode(',', $request->query('role_ids')) : [];
        $is_active = $request->filled('is_active') ? intval($request->query('is_active')) : null;
        $users = new User();
        if ($search) {
            $users = $users->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('username', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }
        if ($is_active !== null) {
            $users = $users->where('is_active', $is_active);
        }
        if (!empty($role_ids)) {
            $users = $users->whereHas('roles', function ($query) use ($role_ids) {
                $query->whereIn('roles.id', $role_ids);
            });
        }

        $users = $users->orderBy($scol, $sdir)
            ->paginate($size, ['*'], 'page', $page);

        return res_paginate($users, 'Users retrieved successfully', UserResource::collection($users));
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get user details by ID (admin)",
     *     tags={"Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return res_fail('User not found');
        return res_success('User retrieved successfully', new UserResource($user));
    }

    // use request form data
    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user (admin)",
     *     tags={"Users"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"first_name", "last_name", "username", "email", "password"},
     *               @OA\Property(property="first_name", type="string",example="User 1"),
     *               @OA\Property(property="last_name", type="string",example="Account"),
     *               @OA\Property(property="username", type="string",example="user1"),
     *               @OA\Property(property="email", type="string",example="user1@gmail.com"),
     *               @OA\Property(property="phone", type="string",example="1234512345"),
     *               @OA\Property(property="password", type="string",example="2345678!@"),
     *              @OA\Property(property="password_confirmation", type="string",example="2345678!@"),
     *               @OA\Property(property="avatar", type="string", format="binary"),
     *               @OA\Property(property="role_ids", type="string", description="Comma-separated role IDs", example="[1,2]"),
     *               @OA\Property(property="is_active", type="integer", enum={0,1}),
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=201,
     *       description="User created successfully"
     *   )
     * )
     */
    public function store(Request $request)
    {
        // Create a new user
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|string|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed', // expects password_confirmation
            'avatar' => 'image|nullable|mimes:jpeg,png,jpg|max:10240',
            'role_ids' => 'nullable|string', // comma-separated role IDs
            'is_active' => 'nullable|integer|in:0,1',
        ]);
        $user = new User();
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->username = $request->input('username');
        $user->phone = $request->input('phone');
        $user->email = $request->input('email');
        $user->password = $request->input('password'); // auto-hashed via User model cast
        $user->is_active = $request->input('is_active', User::IS_ACTIVE['ACTIVE']); // default to active
        // Optional avatar upload
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('avatars', $filename, 'public');
            $user->avatar = $filename;
        }

        $user->save();
        $role_ids = $request->input('role_ids');
        // Accept JSON string or array
        if (is_string($role_ids)) {
            $decoded = json_decode($role_ids, true);
            $role_ids = is_array($decoded) ? $decoded : explode(',', $role_ids);
        }
        $user->roles()->sync($role_ids);
        return res_success('User created successfully', new UserResource($user));
    }

    /**
     * @OA\Post(
     *     path="/api/users/{id}",
     *     summary="Update an existing user (admin)",
     *     tags={"Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               @OA\Property(property="first_name", type="string", example="User 1"),
     *               @OA\Property(property="last_name", type="string", example="User 1"),
     *               @OA\Property(property="username", type="string", example="user1"),
     *               @OA\Property(property="email", type="string", example="user1@gmail.com"),
     *               @OA\Property(property="phone", type="string", example="1234512345"),
     *               @OA\Property(property="password", type="string", example="2345678!@"),
     *               @OA\Property(property="avatar", type="string", format="binary"),
     *               @OA\Property(property="role_ids", type="string", example="[1,2]", description="JSON array of role IDs"),
     *               @OA\Property(property="is_active", type="integer", enum={0,1}),
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User updated successfully"
     *   )
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'username' => 'nullable|string|unique:users,username,' . $id,
            'email' => 'nullable|string|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'role_ids' => 'nullable',
            'is_active' => 'nullable|integer|in:0,1',
        ]);

        $user = User::find($id);
        if (!$user) {
            return res_fail('User not found');
        }

        // Fill attributes dynamically
        $user->fill($request->only([
            'first_name',
            'last_name',
            'username',
            'email',
            'phone',
            'is_active'
        ]));

        // Handle password update
        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        // Handle avatar
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('avatars', $filename, 'public');
            $user->avatar = $filename;
        }

        // Handle roles
        if ($request->filled('role_ids')) {
            $role_ids = $request->input('role_ids');
            // Accept JSON string or array
            if (is_string($role_ids)) {
                $decoded = json_decode($role_ids, true);
                $role_ids = is_array($decoded) ? $decoded : explode(',', $role_ids);
            }
            $user->roles()->sync($role_ids);
        }

        $user->save();
        $user->refresh();
        $user->load('roles');

        return res_success('User updated successfully', new UserResource($user));
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete a user (admin)",
     *     tags={"Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        // Delete a user
        $user = User::find($id);
        if (!$user) return res_fail('User not found');
        $user->roles()->detach(); // remove role associations
        // delete avatar file if exists
        if ($user->avatar) {
            $avatarPath = storage_path('app/public/avatars/' . $user->avatar);
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
        }
        $user->delete();
        return res_success('User deleted successfully', []);
    }
}
