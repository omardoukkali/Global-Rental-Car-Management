<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class CheckCarAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_at' => [
                'required',
                'date',
            ],

            'end_at' => [
                'required',
                'date',
                'after:start_at',
            ],
        ];
    }
}