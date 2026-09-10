<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
        ];
    }
}