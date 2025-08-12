<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function forgetPassword(ForgotPasswordRequest $request)
    {
        // Validate the email address provided in the request
        $validated = $request->validated();

        // Use Laravel's Password facade to send the reset link.
        // This will generate a token, store it in the 'password_reset_tokens' table,
        // and send an email to the user with the reset link.
        Password::sendResetLink(
            $validated->only('email')
        );



        return response()->json([
            'status' => 'success',
            'message' => 'Password reset link sent successfully. Please check your email.',
        ], 200);
    }
}
