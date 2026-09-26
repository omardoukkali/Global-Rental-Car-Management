<?php

namespace App\Http\Requests\SmartDrive;

use Illuminate\Foundation\Http\FormRequest;

class SmartDriveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // "Votre trajet"
            'budget_per_day' => [
                'required',
                'numeric',
                'min:0',
            ],

            'start_at' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'end_at' => [
                'required',
                'date',
                'after:start_at',
            ],

            // Pickup city
            'city_id' => [
                'required',
                'uuid',
                'exists:cities,id',
            ],

            // "Votre véhicule"
            'passengers' => [
                'required',
                'integer',
                'min:1',
                'max:9',
            ],

            // Preferences: optional, sent to the AI for scoring
            'vehicle_type' => [
                'nullable',
                'in:sedan,suv,hatchback,coupe,van,truck',
            ],

            'transmission' => [
                'nullable',
                'in:manual,automatic',
            ],

            'energy_type' => [
                'nullable',
                'in:gasoline,diesel,hybrid,electric',
            ],
        ];
    }
}
