<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAgencyPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim($this->input('name'));
        }

        if ($this->has('address')) {
            $data['address'] = trim($this->input('address'));
        }

        if ($this->has('instructions')) {
            $data['instructions'] = trim($this->input('instructions'));
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'city_id' => [
                'sometimes',
                'uuid',
                'exists:cities,id',
            ],

            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'address' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'latitude' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'allows_pickup' => [
                'sometimes',
                'boolean',
            ],

            'allows_return' => [
                'sometimes',
                'boolean',
            ],

            'opening_hours' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'instructions' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $pickup = $this->has('allows_pickup')
                    ? $this->boolean('allows_pickup')
                    : true;

                $return = $this->has('allows_return')
                    ? $this->boolean('allows_return')
                    : true;

                if (!$pickup && !$return) {
                    $validator->errors()->add(
                        'allows_pickup',
                        'At least pickup or return must be allowed.'
                    );
                }
            },
        ];
    }
}
