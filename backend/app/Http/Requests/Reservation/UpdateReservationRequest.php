<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'client';
    }

    public function rules(): array
    {
        return [
            'pickup_point_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:agency_points,id',
            ],

            'return_point_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:agency_points,id',
            ],

            'start_at' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:now',
            ],

            'end_at' => [
                'sometimes',
                'required',
                'date',
                'after:start_at',
            ],
        ];
    }
}