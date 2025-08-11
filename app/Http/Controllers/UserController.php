<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth; // <-- Add this for Auth
use Symfony\Component\HttpFoundation\Response; // <-- Add this for Response


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
        public function show(string $id)
    {
        // Find the user requested by the ID in the URL.
        // If not found, a ModelNotFoundException is thrown and caught by Handler.php.
        $requestedUser = User::findOrFail($id);
        // Get the authenticated user.
        // We assume the user is authenticated because the route is protected by middleware.
        $authenticatedUser = Auth::user();
        // Authorization check: Is the authenticated user the same as the requested user?
        if ($authenticatedUser->id != $requestedUser->id) {
            // If the IDs do not match, the user is not authorized.
            return response()->json([
                'error' => 'You are not authorized to view this user.'
            ], Response::HTTP_FORBIDDEN);
        }

        // If the authorization check passes, return the user data.
        $userData = $requestedUser->toArray();
        $userData['message'] = 'Data retrived with success';

        return response()->json($userData, Response::HTTP_OK);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function changePassword(ChangePasswordRequest $request) 
    {
    /** @var \App\Models\User $user */
        // Get the authenticated user.
        $user = Auth::user();
        $user->password = Hash::make($request->new_password);
        $user->password_changed_at=now();
        $user->save();
        return response()->json([
            'message' => 'Password changed successfully',
        ], 200);
    }

    public function update(UpdateProfileRequest $request, string $id)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->update($request->all());
        return response()->json(['message' => 'User updated successfully', 'data' => $user], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
