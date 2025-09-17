<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/profile/update",
     *      summary="Update the authenticated user's profile",
     *      tags={"Profile"},
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"first_name","last_name"},
     *                  @OA\Property(property="first_name", type="string", example="Hinsy"),
     *                  @OA\Property(property="last_name", type="string", example="Uon"),
     *                  @OA\Property(property="email", type="string", example="hinsy.uon@example.com"),
     *                  @OA\Property(property="phone", type="string", example="1234567890"),
     *                  @OA\Property(
     *                      property="avatar",
     *                      type="string",
     *                      format="binary",
     *                      description="Avatar image file (jpeg, png, jpg), max 10MB"
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="User account updated successfully")
     * )
     */
    public function update(Request $req)
    {
        $req->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'nullable|string|email|unique:users,email,' . $req->user('sanctum')->id,
            'phone'      => 'nullable|string',
            'avatar'     => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        $user = User::find($req->user('sanctum')->id);
        if (!$user) {
            return res_fail('User not found');
        }

        // Update basic fields
        $user->fill($req->only(['first_name', 'last_name', 'email', 'phone']));

        // Handle avatar
        if ($req->hasFile('avatar')) {
            if ($user->avatar && $user->avatar !== User::NO_IMAGE) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }

            $file = $req->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('avatars', $filename, 'public');

            $user->avatar = $filename;
        }

        $user->save();
        $user->refresh();

        return res_success('User has updated successfully', new AuthResource($user));
    }

    /**
     * @OA\Put(
     *      path="/api/profile/update-password",
     *      summary="Update the authenticated user's password",
     *      tags={"Profile"},
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"current_password","new_password","confirm_new_password"},
     *              @OA\Property(property="current_password", type="string", example="1234abc!"),
     *              @OA\Property(property="new_password", type="string", example="abc1234!"),
     *              @OA\Property(property="confirm_new_password", type="string", example="abc1234!")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Password updated successfully")
     * )
     */
    public function updatePassword(Request $req)
    {
        $req->validate([
            'current_password'     => 'required|string',
            'new_password'         => 'required|string|min:8',
            'confirm_new_password' => 'required|string|same:new_password',
        ]);

        $user = User::find($req->user('sanctum')->id, ['id', 'password']);
        if (!$user) {
            return res_fail('User not found');
        }

        if (!Hash::check($req->input('current_password'), $user->password)) {
            return res_fail('Current password is incorrect');
        }

        $user->password = Hash::make($req->input('new_password'));
        $user->save();

        return res_success('Password has updated successfully', []);
    }
}
