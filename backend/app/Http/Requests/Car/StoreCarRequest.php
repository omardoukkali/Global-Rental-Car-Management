<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCarRequest extends FormRequest
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

            'brand' => [
                'required',
                'string',
                'max:255',
            ],

            'model' => [
                'required',
                'string',
                'max:255',
            ],

            'year' => [
                'required',
                'integer',
                'min:1900',
                'max:' . (now()->year + 1),
            ],

            'color' => [
                'nullable',
                'string',
                'max:100',
            ],

            'plate_number' => [
                'required',
                'string',
                'max:255',
                'unique:cars,plate_number',
            ],

            'type' => [
                'required',
                'in:sedan,suv,hatchback,coupe,van,truck',
            ],

            'transmission' => [
                'required',
                'in:manual,automatic',
            ],

            'seats' => [
                'required',
                'integer',
                'min:1',
                'max:50',
            ],

            'daily_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'energy_type' => [
                'required',
                'in:gasoline,diesel,hybrid,electric',
            ],

            'fuel_consumption' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'electric_range' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'images' => [
                'sometimes',
                'array',
                'max:10',
            ],

            'images.*.url' => [
                'required',
                'url',
                'max:2048',
            ],

            'images.*.is_primary' => [
                'sometimes',
                'boolean',
            ],

            'images.*.display_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $images = $this->input('images', []);

                $primaryCount = collect($images)
                    ->filter(fn ($image) => ($image['is_primary'] ?? false) === true)
                    ->count();

                if ($primaryCount > 1) {
                    $validator->errors()->add(
                        'images',
                        'Only one image can be primary.'
                    );
                }
            },
        ];
    }
}