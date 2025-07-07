<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        //Registration logic
        $user = User::Create([
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'gender' => $request->gender,
            'country' => $request->country,
            'city' => $request->city,
            'birthday' => $request->birthday,
            'userName' => $request->usesrName,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'accountType' => $request->accountType,
        ]);

        return response()->json(['user' => $user], 201);
    }
}
