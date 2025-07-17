<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;


class ResetPasswordController extends Controller
{
    public function reset(ResetPasswordRequest $request)
    {
        $validated = $request->validated();

        $response = Password::broker()->reset(
            $validated->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($validated) {
                // This callback is executed if the token is valid and the password can be reset.

                // Update the user's password with the new hashed password
                $user->forceFill([
                    'password' => Hash::make($validated->password),                                                                                                                               
                ])->setRememberToken(null); // Clear any 'remember me' token if present

                // Update the 'password_changed_at' timestamp.
                // This is crucial for invalidating all existing JWTs for this user.
                // Any JWT issued before this timestamp will be considered invalid by our middleware.                                                                                                                                                                                                                                                                          
                $user->password_changed_at = now();

                $user->save(); // Save the updated user model

                // Dispatch the PasswordReset event
                event(new PasswordReset($user));

                // The JWT invalidation is handled by the 'password_changed_at'
                // mechanism in the User model and JwtAuthMiddleware.
            }
        );

        // Check the response status from the Password broker
        if ($response == Password::PASSWORD_RESET) {
            // If the password was successfully reset, return a success message
            return response()->json([
                'status' => 'success',
                'message' => 'Password has been reset successfully. Please log in with your new password.',
            ], 200);
        }

        // If the password could not be reset (e.g., invalid token, expired token),
        // throw a validation exception with the appropriate message
        throw ValidationException::withMessages([
            'email' => [trans($response)], // Laravel provides translatable messages for these responses
        ]);
    }
}
