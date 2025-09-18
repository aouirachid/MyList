<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstName' => 'string|max:255',
            'lastName' => 'string|max:255',
            'gender' => 'in:male,female,other',
            'country' => 'string|max:255',
            'city' => 'string|max:255',
            'birthday' => 'date|before:today',
            'userName' => 'string|max:255|unique:users,userName',
            'email' => 'string|email|max:255|unique:users,email',
            'phone' => 'string|max:20',
            'password' => 'string|min:10',
            'accountType' => 'string|max:255',
        ];
    }
}
