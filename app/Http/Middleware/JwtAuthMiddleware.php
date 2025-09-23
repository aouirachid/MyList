<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth; // Import the User model

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = null;

        // Get token from Authorization header
        $authHeader = $request->header('Authorization');
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        if (! $token) {
            return response()->json(['status' => 'error', 'message' => 'Token not provided'], 401);
        }

        try {
            // Attempt to parse the token and authenticate the user
            $user = JWTAuth::parseToken()->authenticate();

            // If user is null, token is invalid or user not found
            if (! $user) {
                return response()->json(['status' => 'error', 'message' => 'User not found or token invalid'], 404);
            }

            // Get the 'password_changed_at' claim from the token payload
            $tokenPayload = JWTAuth::parseToken()->getPayload();
            $tokenPasswordChangedAt = $tokenPayload->get('password_changed_at');

            // Get the user's current 'password_changed_at' from the database
            // Ensure the user object retrieved from the database has the latest password_changed_at
            $latestUser = User::find($user->id);

            if (! $latestUser) {
                return response()->json(['status' => 'error', 'message' => 'User not found in database'], 404);
            }

            // Compare the timestamp from the token with the latest from the database
            // If the token was issued before the last password change, it's invalid
            if ($latestUser->password_changed_at && $tokenPasswordChangedAt < $latestUser->password_changed_at->timestamp) {
                return response()->json(['status' => 'error', 'message' => 'Token invalidated due to password change. Please log in again.'], 401);
            }

            // Set the authenticated user on the request attributes
            $request->attributes->set('jwt_user', $user);

        } catch (TokenExpiredException $e) {
            return response()->json(['status' => 'error', 'message' => 'Token has expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['status' => 'error', 'message' => 'Token is invalid'], 401);
        } catch (JWTException $e) {
            // Catch any other JWT related exceptions
            return response()->json(['status' => 'error', 'message' => 'Token error: '.$e->getMessage()], 401);
        } catch (Exception $e) {
            // Catch any other general exceptions
            return response()->json(['status' => 'error', 'message' => 'An unexpected error occurred: '.$e->getMessage()], 500);
        }

        return $next($request);

    }
}
