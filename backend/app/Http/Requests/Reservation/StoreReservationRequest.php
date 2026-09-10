<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'client';
    }

    public function rules(): array
    {
        return [
            'car_id' => [
                'required',
                'uuid',
                'exists:cars,id',
            ],

            'pickup_point_id' => [
                'required',
                'uuid',
                'exists:agency_points,id',
            ],

            'return_point_id' => [
                'required',
                'uuid',
                'exists:agency_points,id',
            ],

            'start_at' => [
                'required',
                'date',
                'after_or_equal:now',
            ],

            'end_at' => [
                'required',
                'date',
                'after:start_at',
            ],
        ];
    }
}