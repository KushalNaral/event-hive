<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
}
