<?php

namespace App\Http\Requests\Car\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesEnergyConsistency
{
    /**
     * Vérifie la cohérence entre le type d'énergie et les champs
     * fuel_consumption (L/100km) et electric_range (km).
     *
     * Règle métier :
     *   - gasoline / diesel : fuel_consumption requis, electric_range interdit
     *   - electric          : electric_range requis, fuel_consumption interdit
     *   - hybrid            : les deux requis
     *
     * L'exclusivité thermique/électrique découle des ensembles ci-dessous.
     */
    protected function validateEnergyConsistency(Validator $validator): void
    {
        $data = $validator->getData();

        // Pas de type d'énergie fourni (ex. mise à jour partielle) : rien à vérifier.
        if (! array_key_exists('energy_type', $data) || $data['energy_type'] === null) {
            return;
        }

        $energyType = $data['energy_type'];
        $fuelConsumption = $data['fuel_consumption'] ?? null;
        $electricRange = $data['electric_range'] ?? null;

        $requiresFuel = in_array($energyType, ['gasoline', 'diesel', 'hybrid'], true);
        $requiresRange = in_array($energyType, ['electric', 'hybrid'], true);

        // --- fuel_consumption ---
        if ($requiresFuel && $fuelConsumption === null) {
            $validator->errors()->add(
                'fuel_consumption',
                "La consommation (L/100km) est requise pour un véhicule {$energyType}."
            );
        }

        if (! $requiresFuel && $fuelConsumption !== null) {
            $validator->errors()->add(
                'fuel_consumption',
                "La consommation (L/100km) doit être vide pour un véhicule {$energyType}."
            );
        }

        // --- electric_range ---
        if ($requiresRange && $electricRange === null) {
            $validator->errors()->add(
                'electric_range',
                "L'autonomie électrique (km) est requise pour un véhicule {$energyType}."
            );
        }

        if (! $requiresRange && $electricRange !== null) {
            $validator->errors()->add(
                'electric_range',
                "L'autonomie électrique (km) doit être vide pour un véhicule {$energyType}."
            );
        }
    }
}
