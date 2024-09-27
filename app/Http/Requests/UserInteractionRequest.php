<?php

namespace App\Http\Requests;

use App\Models\UserInteractions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UserInteractionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function interactions(): array
    {
        return UserInteractions::interactions();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'interaction_type' => ['required', Rule::in(self::interactions())],
        ];
    }

    public function attributes(): array
    {
        return [
            'event_id' => 'Event',
            'interaction_type' => 'Interaction Type',
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'The :attribute is required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = errorResponse("Please check the form again.", 422, $validator->errors());
        throw new ValidationException($validator, $response);
    }
}
