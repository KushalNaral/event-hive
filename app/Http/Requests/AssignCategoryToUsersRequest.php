<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class AssignCategoryToUsersRequest extends FormRequest
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
            'category_id' => 'required|array|exists:event_categories,id',
            'user_id' => 'required|exists:users,id', //here will this be provided from the back or front confused
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'Event Category',
            'user_id' => 'User',
        ];
    }

    public function messages(): array
    {
        return [
            '*.exists' => 'The selected :attribute does not exist.',
        ];
    }

    //the method here overrides the default validation error format
    protected function failedValidation(Validator $validator)
    {
        $response = errorResponse("Please check the form again.", 422, $validator->errors());
        throw new ValidationException($validator, $response);
    }
}
