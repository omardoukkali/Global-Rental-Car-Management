<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_id' => [
                'sometimes',
                'uuid',
                'exists:cities,id',
            ],

            'brand' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'model' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'year' => [
                'sometimes',
                'integer',
                'min:1900',
                'max:' . (now()->year + 1),
            ],

            'color' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'plate_number' => [
                'sometimes',
                'string',
                'max:255',
                'unique:cars,plate_number,' . $this->route('car')?->id,
            ],

            'type' => [
                'sometimes',
                'in:sedan,suv,hatchback,coupe,van,truck',
            ],

            'transmission' => [
                'sometimes',
                'in:manual,automatic',
            ],

            'seats' => [
                'sometimes',
                'integer',
                'min:1',
                'max:50',
            ],

            'daily_price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'energy_type' => [
                'sometimes',
                'in:gasoline,diesel,hybrid,electric',
            ],

            'fuel_consumption' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'electric_range' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}