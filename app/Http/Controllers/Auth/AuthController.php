<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        //Registration logic

        // Retrieve the validated input data
        $validated = $request->validated();


        $user = User::Create([
            'firstName' => $validated->firstName,
            'lastName' => $validated->lastName,
            'gender' => $validated->gender,
            'country' => $validated->country,
            'city' => $validated->city,
            'birthday' => $validated->birthday,
            'userName' => $validated->userName,
            'email' => $validated->email,
            'phone' => $validated->phone,
            'password' => Hash::make($validated->password),
            'accountType' => $validated->accountType,
        ]);

        //Generate a token for the user
        $token = JWTAuth::fromUser($user);

        //Return the response with the token and the user data
        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ],
            201
        ]);
    }

    public function refreshToken()
    {
        try {
            //Refresh the token
            $token = JWTAuth::refresh(JWTAuth::getToken());
            //Return the response with the new token and the expiration time
            return response()->json([
                'token' => $token,
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }
public function login (LoginRequest $request)
{
    $validated=$request->validated();
    $loginType = filter_var($validated->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    $credentials = [
        $loginType => $validated->login,
        'password' => $validated->password,
    ];

    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => JWTAuth::factory()->getTTL() * 60
    ]);
}

public function Logout()
{
    try {
        JWTAuth::invaliddate(JWTAuth::getToken());
    } catch (JWTException $e) {
        return response()->json(['error' => 'Failed to logout ,please try again'], 500);
    }
    return response()->json(['message'=>'Successfuly logged out'],200);

}
}
