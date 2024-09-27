<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class PreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules()
    {
           return [
            'preferred_seasons' => 'nullable|array',
            'preferred_seasons.*' => 'string|in:winter,summer,fall,spring',
            'preferred_event_sizes' => 'nullable|array',
            'preferred_event_sizes.*' => 'string|in:intimate,medium,large',
            'preferred_days' => 'nullable|array',
            'preferred_days.*' => 'string|in:weekday,weekend',
            'preferred_themes' => 'nullable|array',
            'preferred_themes.*' => 'string',
            'preferred_times_of_day' => 'nullable|array',
            'preferred_times_of_day.*' => 'string|in:morning,afternoon,evening,night',
            'preferred_categories' => 'nullable|array',
            'preferred_categories.*' => 'string',
            'preferred_duration_days' => 'nullable|array',
            'preferred_duration_days.*' => 'integer|min:1|max:30',
            'preferred_location_types' => 'nullable|array',
            'preferred_location_types.*' => 'string|in:urban,suburban,rural',
            'preferred_formality_levels' => 'nullable|array',
            'preferred_formality_levels.*' => 'string|in:casual,semi-formal,formal',
        ];
    }

    public function messages()
    {
        return [
            'preferred_seasons.array' => 'Preferred seasons must be an array.',
            'preferred_seasons.*.string' => 'Each preferred season must be a string.',
            'preferred_seasons.*.in' => 'Preferred season must be one of the following: winter, summer, fall, spring.',
            'preferred_event_sizes.array' => 'Preferred event sizes must be an array.',
            'preferred_event_sizes.*.string' => 'Each preferred event size must be a string.',
            'preferred_event_sizes.*.in' => 'Preferred event size must be one of the following: intimate, medium, large.',
            'preferred_days.array' => 'Preferred days must be an array.',
            'preferred_days.*.string' => 'Each preferred day must be a string.',
            'preferred_days.*.in' => 'Preferred day must be either weekday or weekend.',
            'preferred_themes.array' => 'Preferred themes must be an array.',
            'preferred_times_of_day.array' => 'Preferred times of day must be an array.',
            'preferred_times_of_day.*.string' => 'Each preferred time of day must be a string.',
            'preferred_times_of_day.*.in' => 'Preferred time of day must be one of the following: morning, afternoon, evening, night.',
            'preferred_categories.array' => 'Preferred categories must be an array.',
            'preferred_duration_days.array' => 'Preferred duration days must be an array.',
            'preferred_duration_days.*.integer' => 'Each preferred duration day must be an integer.',
            'preferred_duration_days.*.min' => 'Preferred duration day must be at least 1 day.',
            'preferred_duration_days.*.max' => 'Preferred duration day may not be greater than 30 days.',
            'preferred_location_types.array' => 'Preferred location types must be an array.',
            'preferred_location_types.*.string' => 'Each preferred location type must be a string.',
            'preferred_location_types.*.in' => 'Preferred location type must be one of the following: urban, suburban, rural.',
            'preferred_formality_levels.array' => 'Preferred formality levels must be an array.',
            'preferred_formality_levels.*.string' => 'Each preferred formality level must be a string.',
            'preferred_formality_levels.*.in' => 'Preferred formality level must be one of the following: casual, semi-formal, formal.',
        ];
    }

    public function attributes()
    {
        return [
            'preferred_seasons' => 'preferred seasons',
            'preferred_event_sizes' => 'preferred event sizes',
            'preferred_days' => 'preferred days',
            'preferred_themes' => 'preferred themes',
            'preferred_times_of_day' => 'preferred times of day',
            'preferred_categories' => 'preferred categories',
            'preferred_duration_days' => 'preferred duration days',
            'preferred_location_types' => 'preferred location types',
            'preferred_formality_levels' => 'preferred formality levels',
        ];
    }


    //the method here overrides the default validation error format
    protected function failedValidation(Validator $validator)
    {
        $response = errorResponse("Please check the form again.", 422, $validator->errors());
        throw new ValidationException($validator, $response);
    }
}
