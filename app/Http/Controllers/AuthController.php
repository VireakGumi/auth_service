<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/auth/me",
     *      summary="To get current login user profile (Owner)",
     *      tags={"Authentication"},
     *      security={{"sanctum":{}}},
     *      @OA\Response(response="200", description="Get user profile successfully")
     * )
     */
    public function me(Request $req)
    {
        $user = User::where('id', $req->user('sanctum')->id)->first();
        if (!$user) return res_fail('User not found');

        return res_success('Get user profile successfully', new AuthResource($user));
    }

    /**
     * @OA\Post(
     *   path="/api/auth/register",
     *   summary="Register a new user",
     *   security={},
     *   tags={"Authentication"},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"first_name", "last_name", "username", "email", "password", "password_confirmation"},
     *               @OA\Property(property="first_name", type="string", example="John"),
     *               @OA\Property(property="last_name", type="string", example="Doe"),
     *               @OA\Property(property="username", type="string", example="johndoe"),
     *               @OA\Property(property="email", type="string", example="john@example.com"),
     *               @OA\Property(property="phone", type="string", example="123-456-7890"),
     *               @OA\Property(property="password", type="string", example="secret123"),
     *               @OA\Property(property="password_confirmation", type="string", example="secret123"),
     *               @OA\Property(property="avatar", type="string", format="binary", description="Optional avatar image")
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User registered successfully"
     *   )
     * )
     */

    public function register(Request $req)
    {
        $req->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'username'   => 'required|string|max:50|unique:users,username',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable|string|max:20',
            'password'   => 'required|string|min:8|confirmed', // expects password_confirmation
            'avatar'     => 'nullable|image|mimes:jpeg,png,jpg|max:10240', // optional
        ]);

        $user = new User();
        $user->first_name = $req->input('first_name');
        $user->last_name  = $req->input('last_name');
        $user->username   = $req->input('username'); // new field
        $user->email      = $req->input('email');
        $user->phone      = $req->input('phone'); // new field
        $user->email_verified_at = Carbon::now();
        $user->password   = $req->input('password'); // auto-hashed via User model cast

        // Optional avatar upload
        if ($req->hasFile('avatar')) {
            $file = $req->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('avatars', $filename, 'public');
            $user->avatar = $filename;
        }

        $user->save();

        // Assign default 'user' role (assuming role ID 2 is 'user')
        $user->roles()->sync([2]);
        $user->save();

        // Create Sanctum token
        $token = $user->createToken($user->id)->plainTextToken;
        $user->token = $token;

        return res_success('User registered successfully', new AuthResource($user));
    }

    /**
     * @OA\Post(
     *   path="/api/auth/login",
     *   summary="To login user",
     *   security={},
     *   tags={"Authentication"},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *           required={"email", "password" },
     *           @OA\Property(property="email", type="string", example="admin@gmail.com"),
     *           @OA\Property(property="password", type="string", example="11223344Aa!@#"),
     *       )
     *   ),
     *   @OA\Response(response="200", description="Check key result is true login successful, false is login fail")
     * )
     */
    public function login(Request $req)
    {
        $req->validate([
            'email'     => 'string|required|email',
            'password'  => 'string|required'
        ]);
        $user = User::where('email', $req->input('email'))->first(['id', 'email', 'password', 'email_verified_at', 'first_name', 'last_name', 'username', 'avatar']);
        if (!$user) return res_fail('Incorrect email or password');
        if (!Hash::check($req->password, $user->password)) return res_fail('Incorrect email or password');
        $token       = $user->createToken($user->id);
        $user->email_verified_at = Carbon::now();
        $user->save();
        $user->token = $token->plainTextToken;
        return res_success('User login successfully', new AuthResource($user));
    }

    /**
     * @OA\Post(
     *      path="/api/auth/logout",
     *      summary="To logout the current user",
     *      tags={"Authentication"},
     *      security={{"sanctum":{}}},
     *      @OA\Response(response="200", description="Logout successfully")
     * )
     */
    public function logout(Request $req)
    {
        $req->user('sanctum')->currentAccessToken()->delete();
        return res_success('Logout successfully');
    }
}
