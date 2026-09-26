<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class PublicCarIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxPriceRules = [
            'sometimes',
            'numeric',
            'min:0',
        ];

        // Compare with min_price only when it is sent
        if ($this->filled('min_price')) {
            $maxPriceRules[] = 'gte:min_price';
        }

        return [
            'q' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'city_id' => [
                'sometimes',
                'uuid',
            ],

            'type' => [
                'sometimes',
                'in:sedan,suv,hatchback,coupe,van,truck',
            ],

            'transmission' => [
                'sometimes',
                'in:manual,automatic',
            ],

            'energy_type' => [
                'sometimes',
                'in:gasoline,diesel,hybrid,electric',
            ],

            'min_seats' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'min_price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'max_price' => $maxPriceRules,

            'start_at' => [
                'required_with:end_at',
                'date',
            ],

            'end_at' => [
                'required_with:start_at',
                'date',
                'after:start_at',
            ],

            'sort' => [
                'sometimes',
                'in:price_asc,price_desc,newest',
            ],
        ];
    }
}
