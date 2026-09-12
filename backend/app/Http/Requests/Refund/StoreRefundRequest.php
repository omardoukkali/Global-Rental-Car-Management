<?php

namespace App\Http\Requests\Refund;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'agency';
    }

    public function rules(): array
    {
        return [
            'payment_id' => [
                'required',
                'uuid',
                'exists:payments,id',
            ],

            'percentage' => [
                'nullable',
                'numeric',
                'min:50',
                'max:100',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}