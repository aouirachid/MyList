<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
             'firstName' => 'required|string|max:255',
        'lastName' => 'required|string|max:255',
        'userName' => 'required|string|max:255|unique:users,userName',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|string|min:8|confirmed',

        'gender' => 'required|in:male,female,other',
        'country' => 'required|string|max:100',
        'city' => 'required|string|max:100',
        'birthday' => 'required|date|before:today',
        'phone' => 'required|string|max:20',

        'accountType' => 'required|in:10,20,30', // example: 10=admin, 20=client, 30=artisant
        'status' => 'in:0,1', // optional, if set to active/inactive
        ];
    }
}
