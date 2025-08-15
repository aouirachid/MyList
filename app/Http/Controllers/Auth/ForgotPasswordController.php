<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(ForgotPasswordRequest $request)
    {
         // Validate incoming request
    $validated = $request->validated();

    // Extract email
    $email = $validated['email'];
       $user = User::where('email', $email)->first();

    if (!$user) {
        return response()->json([
            'status' => 'success',
            'message' => 'Password reset link sent successfully. Please check your email.'
        ], 200);
    }

    $status = Password::sendResetLink(['email' => $email]);

    if ($status === Password::RESET_LINK_SENT) {
        return response()->json([
            'status' => 'success',
            'message' => 'Password reset link sent successfully. Please check your email.',
        ], 200);
    }

    return response()->json([
        'status' => 'error',
        'message' => __($status),
    ], 400);
    }
}
