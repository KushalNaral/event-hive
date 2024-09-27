<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

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
            '*.required' => 'The :attribute is required',
        ];
    }

    //the method here overrides the default validation error format
    protected function failedValidation(Validator $validator)
    {
        $response = errorResponse("Please check the form again.", 422, $validator->errors());
        throw new ValidationException($validator, $response);
    }

}
