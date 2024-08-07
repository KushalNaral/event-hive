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
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'phone_number' => 'required|min:10|max:10',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'User Name',
            'email' => 'Email Address',
            /* 'password' => 'Password', */
            'phone_number' => 'Phone Number',
        ];
    }

    public function messages(): array
    {
        return [
            '.required' => 'The :key is required',
        ];
    }
}
