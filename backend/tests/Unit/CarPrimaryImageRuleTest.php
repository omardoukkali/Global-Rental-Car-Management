<?php

namespace Tests\Unit;

use App\Http\Requests\Car\StoreCarRequest;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Règle métier : au plus une image peut être principale (is_primary = true)
 * par voiture.
 *
 * Ce test cible la validation groupée de StoreCarRequest::after(), qui rejette
 * tout payload contenant plus d'une image marquée is_primary = true.
 */
class CarPrimaryImageRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Lance la validation complète de StoreCarRequest (rules + after())
     * et renvoie true si l'erreur porte spécifiquement sur "images".
     *
     * On cible la clé "images" plutôt que fails() global pour ne pas être
     * pollué par d'éventuelles autres règles.
     */
    private function imagesRuleFails(array $images): bool
    {
        $data = $this->carData(['images' => $images]);

        // La règle "images" dans after() lit $this->input('images') sur
        // l'INSTANCE de requête (pas $validator->getData()). Il faut donc
        // construire la requête AVEC les données, sinon l'input est vide.
        $request = StoreCarRequest::create('/api/cars', 'POST', $data);

        $validator = Validator::make($data, $request->rules());

        foreach ($request->after() as $afterCallback) {
            $validator->after($afterCallback);
        }

        // Force l'exécution de toutes les règles (y compris after()).
        $validator->fails();

        return $validator->errors()->has('images');
    }

    private function carData(array $overrides = []): array
    {
        $city = City::factory()->create();

        return array_merge([
            'city_id' => $city->id,
            'brand' => 'Dacia',
            'model' => 'Sandero',
            'year' => 2024,
            'color' => 'White',
            'plate_number' => '12345-A-6',
            'type' => 'hatchback',
            'transmission' => 'manual',
            'seats' => 5,
            'daily_price' => 250,
            'energy_type' => 'gasoline',
            'fuel_consumption' => 6.2,
            'electric_range' => null,
        ], $overrides);
    }

    private function image(bool $primary, string $url): array
    {
        return [
            'url' => $url,
            'is_primary' => $primary,
        ];
    }

    // --- Cas valides : 0 ou 1 image principale ---

    public function test_no_primary_image_passes(): void
    {
        $this->assertFalse($this->imagesRuleFails([
            $this->image(false, 'https://cdn.example.com/1.jpg'),
            $this->image(false, 'https://cdn.example.com/2.jpg'),
        ]));
    }

    public function test_exactly_one_primary_image_passes(): void
    {
        $this->assertFalse($this->imagesRuleFails([
            $this->image(true, 'https://cdn.example.com/1.jpg'),
            $this->image(false, 'https://cdn.example.com/2.jpg'),
            $this->image(false, 'https://cdn.example.com/3.jpg'),
        ]));
    }

    public function test_single_primary_image_passes(): void
    {
        $this->assertFalse($this->imagesRuleFails([
            $this->image(true, 'https://cdn.example.com/1.jpg'),
        ]));
    }

    // --- Cas invalides : plus d'une image principale ---

    public function test_two_primary_images_fail(): void
    {
        $this->assertTrue($this->imagesRuleFails([
            $this->image(true, 'https://cdn.example.com/1.jpg'),
            $this->image(true, 'https://cdn.example.com/2.jpg'),
        ]));
    }

    public function test_three_primary_images_fail(): void
    {
        $this->assertTrue($this->imagesRuleFails([
            $this->image(true, 'https://cdn.example.com/1.jpg'),
            $this->image(true, 'https://cdn.example.com/2.jpg'),
            $this->image(true, 'https://cdn.example.com/3.jpg'),
        ]));
    }
}
