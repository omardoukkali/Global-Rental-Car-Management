<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'client';
    }

    public function rules(): array
    {
        return [
            'reservation_id' => [
                'required',
                'uuid',
                'exists:reservations,id',
            ],

            'car_rating' => [
                'required',
                'numeric',
                'min:1',
                'max:5',
            ],

            'agency_rating' => [
                'required',
                'numeric',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}