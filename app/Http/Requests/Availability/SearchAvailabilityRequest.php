<?php

namespace App\Http\Requests\Availability;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchAvailabilityRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'location_id' => [
                'required',
                'integer',
                'exists:locations,id',
            ],
            'check_in' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'check_out' => [
                'required',
                'date_format:Y-m-d',
                'after:check_in',
            ],
            'adults' => [
                'required',
                'integer',
                'min:1',
                'max:30',
            ],
            'children' => [
                'sometimes',
                'integer',
                'min:0',
                'max:30',
            ],
        ];
    }
}
