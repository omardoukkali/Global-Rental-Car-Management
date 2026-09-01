<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAgencyPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_id' => [
                'required',
                'uuid',
                'exists:cities,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'address' => [
                'required',
                'string',
                'max:255',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'allows_pickup' => [
                'boolean',
            ],

            'allows_return' => [
                'boolean',
            ],

            'opening_hours' => [
                'nullable',
                'array',
            ],

            'instructions' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    !$this->boolean('allows_pickup') &&
                    !$this->boolean('allows_return')
                ) {
                    $validator->errors()->add(
                        'allows_pickup',
                        'At least pickup or return must be allowed.'
                    );
                }
            },
        ];
    }
}