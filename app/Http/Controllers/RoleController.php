<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get a list of roles (admin)",
     *     tags={"Roles"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
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

        $roles = new Role();
        if ($search) {
            $roles = $roles->where('name', 'like', '%' . $search . '%');
        }
        $roles = $roles->orderBy($scol, $sdir)->paginate($size, ['*'], 'page', $page);
        return res_paginate($roles, 'Roles fetched successfully', RoleResource::collection($roles));
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Create a new role (admin)",
     *     tags={"Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="editor"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *     )
     * )
     */
    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = new Role();
        $role->name = $request->input('name');
        $role->save();

        return res_success('Role created successfully', new RoleResource($role), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/roles/{id}",
     *     summary="Get role details by ID (admin)",
     *     tags={"Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role details retrieved successfully",
     *     )
     * )
     */
    public function show($id){
        $role = Role::find($id);
        if (!$role) {
            return res_fail('Role not found', 404);
        }
        return res_success('Role details retrieved successfully', new RoleResource($role));
    }

    /**
     * @OA\Put(
     *     path="/api/roles/{id}",
     *     summary="Update role details by ID (admin)",
     *     tags={"Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="editor"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *     )
     * )
     */
    public function update(Request $request, $id){
        $role = Role::find($id);
        if (!$role) {
            return res_fail('Role not found', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|unique:roles,name,' . $role->id,
        ]);

        if ($request->filled('name')) {
            $role->name = $request->input('name');
        }
        $role->save();

        return res_success('Role updated successfully', new RoleResource($role));
    }

    /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Delete role by ID (admin)",
     *     tags={"Roles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully",
     *     )
     * )
     */
    public function destroy($id){
        $role = Role::find($id);
        if (!$role) {
            return res_fail('Role not found', 404);
        }
        $role->delete();
        return res_success('Role deleted successfully');
    }
}
