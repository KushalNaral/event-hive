<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class EventRatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected const def_ratings = [0,1,2,3,4,5];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => [ 'required' , Rule::in(self::def_ratings) ],
            'event_id' => 'required|exists:events,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'event_id' => 'Event',
            'rating' => 'Event Rating',
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
