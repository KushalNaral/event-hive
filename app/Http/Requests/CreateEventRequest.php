<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class CreateEventRequest extends FormRequest
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
            'title' => 'required',
            'description' => 'nullable',
            'start_date' => 'date', //to set start time you need to send the format as such yyyy/mm/dd hh:mm, on the basis of this attributes are set
            'end_date' => 'date',
            'location' => 'required',
            'expected_participants' => 'required|gte:0',
            'category_id' => 'required|exists:event_categories,id',
        ];
    }

    public function attributes():array
    {
        return [
            'title' => 'Title',
            'description' => 'Description',
            'start_date' => 'Starting Date',
            'end_date' => 'Ending Date',
            'location' => 'Location of Event',
            'expected_participants' => 'Expected Participants',
            'category_id' => 'Event Category',
        ];
    }

    public function messages():array
    {
        return [
            '*.required' => 'The :attribute field is required.',
            '*.exists' => 'The said :attribute does not exist.'
        ];
    }

    //the method here overrides the default validation error format
    protected function failedValidation(Validator $validator)
    {
        $response = errorResponse("Please check the form again.", 422, $validator->errors());
        throw new ValidationException($validator, $response);
    }
}
