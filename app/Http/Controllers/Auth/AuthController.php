<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\LoginResponseResource;
use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'firstName' => $validated['firstName'],
            'lastName' => $validated['lastName'],
            'gender' => $validated['gender'],
            'country' => $validated['country'],
            'city' => $validated['city'],
            'birthday' => $validated['birthday'],
            'userName' => $validated['userName'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'accountType' => $validated['accountType'],
            'status'=>$validated['status'],
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
            
        ],201);
    }

   // Conceptual example - actual implementation depends on your JWT/token library
public function refreshToken(LoginRequest $request)
{
    try {
        // 1. Get the old refresh token from the request header (or body, if you send it differently)
        $oldRefreshToken = $request->bearerToken(); // Assuming it's sent as a bearer token
        $newAccessToken = JWTAuth::refresh(JWTAuth::getToken()); // This refreshes the *access* token

        $newRefreshToken = 'some_new_refresh_token_string'; // Placeholder: Replace with actual generation

        // Return the response with the new access token and new refresh token
        return response()->json([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken, // Crucial for your acceptance criteria
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    } catch (JWTException $e) {
        // Log the error for debugging
        Log::error('Token refresh failed: ' . $e->getMessage());
        return response()->json(['error' => 'Could not refresh token'], 401); // 401 for unauthenticated/invalid token
    }
}
  

public function login(LoginRequest $request)
{
    $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    $credentials = [
        $loginType => $request->login,
        'password' => $request->password,
    ];

    if (!$token = auth()->attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => JWTAuth::factory()->getTTL() * 60
    ]);
}


    /**
     * Refresh a JWT token and invalidate the old refresh token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
 
public function logout()
{
    try {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Successfully logged out'], 200);
    } catch (JWTException $e) {
        return response()->json(['error' => 'Failed to logout, please try again'], 500);
    }
    return response()->json(['message'=>'Successfuly logged out'],200);

}
}
