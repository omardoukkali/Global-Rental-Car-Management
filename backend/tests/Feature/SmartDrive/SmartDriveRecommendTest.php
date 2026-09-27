<?php

namespace Tests\Feature\SmartDrive;

use App\Models\Agency;
use App\Models\AgencyPoint;
use App\Models\Car;
use App\Models\City;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SCRUM-181 — end-to-end validation of POST /api/smartdrive/recommend.
 *
 * The controller (SCRUM-180) applies the rental business rules, forwards the
 * eligible vehicles to the FastAPI service and returns its recommendation.
 * The AI service itself is faked here so the test stays deterministic and
 * offline; the Python scoring is covered by the AI unittest suite.
 */
class SmartDriveRecommendTest extends TestCase
{
    use RefreshDatabase;

    private const AI_URL = 'http://ai_service:5000';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ai.url' => self::AI_URL, 'services.ai.timeout' => 5]);
    }

    /** A city with an approved agency that has an active pickup/return point in it. */
    private function cityWithAgency(): array
    {
        $city = City::factory()->create();

        $owner = User::factory()->create(['role' => 'agency', 'status' => 'active']);
        $agency = Agency::factory()->approved()->create([
            'owner_id' => $owner->id,
            'avg_rating' => 4.60,
            'total_reviews' => 120,
        ]);

        $agency->agencyPoints()->create([
            'city_id' => $city->id,
            'name' => 'Point centre-ville',
            'address' => '1 Rue de Test',
            'allows_pickup' => true,
            'allows_return' => true,
            'is_active' => true,
        ]);

        return [$city, $agency];
    }

    private function availableCar(Agency $agency, City $city, array $overrides = []): Car
    {
        return $agency->cars()->create(array_merge([
            'city_id' => $city->id,
            'brand' => 'Dacia',
            'model' => 'Duster',
            'year' => 2023,
            'color' => 'Gris',
            'plate_number' => 'SD181-' . Str::upper(Str::random(8)),
            'type' => 'suv',
            'transmission' => 'automatic',
            'seats' => 5,
            'daily_price' => 400,
            'energy_type' => 'diesel',
            'fuel_consumption' => 6.0,
            'status' => 'available',
        ], $overrides));
    }

    private function validPayload(City $city, array $overrides = []): array
    {
        return array_merge([
            'budget_per_day' => 500,
            'start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_at' => now()->addDays(6)->format('Y-m-d H:i:s'),
            'city_id' => $city->id,
            'passengers' => 3,
            'vehicle_type' => 'suv',
            'transmission' => 'automatic',
            'energy_type' => 'diesel',
        ], $overrides);
    }

    /** A recommendation body shaped like the FastAPI service returns. */
    private function fakeAiRecommendation(Car $car): array
    {
        $vehicle = [
            'id' => $car->id,
            'brand' => $car->brand,
            'model' => $car->model,
            'type' => $car->type,
            'transmission' => $car->transmission,
            'seats' => $car->seats,
            'energy_type' => $car->energy_type,
            'year' => $car->year,
            'daily_price' => $car->daily_price,
            'total_price' => 1600,
            'agency' => ['name' => 'Atlas', 'avg_rating' => 4.6, 'total_reviews' => 120],
            'score' => 88.0,
            'score_source' => 'ml',
            'confidence' => 92,
            'explanation' => [
                'strengths' => ['respecte votre budget', 'correspond au type demandé'],
                'tradeoffs' => [],
                'summary' => 'respecte votre budget ; correspond au type demandé',
            ],
        ];

        return [
            'trip' => ['city_id' => $car->city_id, 'days' => 4, 'passengers' => 3],
            'preferences' => ['budget_per_day' => 500],
            'total' => 1,
            'recommended' => $vehicle,
            'results' => [$vehicle],
            'alternatives' => ['best_value' => $vehicle, 'most_comfortable' => $vehicle],
            'source' => 'laravel',
        ];
    }

    public function test_valid_preferences_forward_eligible_vehicles_and_return_the_ai_recommendation(): void
    {
        [$city, $agency] = $this->cityWithAgency();
        $car = $this->availableCar($agency, $city);

        Http::fake([
            self::AI_URL . '/api/recommend' => Http::response($this->fakeAiRecommendation($car), 200),
        ]);

        $this->postJson('/api/smartdrive/recommend', $this->validPayload($city))
            ->assertOk()
            ->assertJsonPath('source', 'laravel')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('recommended.id', $car->id)
            ->assertJsonPath('recommended.score', fn ($score) => (int) $score === 88)
            ->assertJsonPath('recommended.confidence', 92)
            ->assertJsonPath('recommended.total_price', 1600)
            ->assertJsonPath('recommended.explanation.summary', 'respecte votre budget ; correspond au type demandé')
            ->assertJsonPath('alternatives.best_value.id', $car->id);

        // The controller must send the eligible vehicles (with agency + prices) to the AI.
        Http::assertSent(function ($request) use ($car) {
            return $request->url() === self::AI_URL . '/api/recommend'
                && is_array($request['vehicles'])
                && count($request['vehicles']) === 1
                && $request['vehicles'][0]['id'] === $car->id
                && $request['vehicles'][0]['total_price'] === 1600.0
                && $request['budget_per_day'] === 500.0;
        });
    }

    public function test_invalid_preferences_are_rejected_with_422_without_calling_the_ai(): void
    {
        [$city, $agency] = $this->cityWithAgency();
        $this->availableCar($agency, $city);

        Http::fake();

        // energy_type not in the allowed list + end_at before start_at.
        $this->postJson('/api/smartdrive/recommend', $this->validPayload($city, [
            'energy_type' => 'nuclear',
            'end_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['energy_type', 'end_at']);

        Http::assertNothingSent();
    }

    public function test_no_eligible_vehicle_returns_an_empty_result_without_calling_the_ai(): void
    {
        [$city, $agency] = $this->cityWithAgency();
        // The only car needs more seats than it has -> not eligible for 5 passengers.
        $this->availableCar($agency, $city, ['seats' => 4]);

        Http::fake();

        $this->postJson('/api/smartdrive/recommend', $this->validPayload($city, ['passengers' => 5]))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('results', []);

        Http::assertNothingSent();
    }

    public function test_a_car_booked_for_the_same_dates_is_not_eligible(): void
    {
        [$city, $agency] = $this->cityWithAgency();
        $car = $this->availableCar($agency, $city);
        $client = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $point = $agency->agencyPoints()->first();

        Reservation::create([
            'client_id' => $client->id,
            'car_id' => $car->id,
            'agency_id' => $agency->id,
            'pickup_point_id' => $point->id,
            'return_point_id' => $point->id,
            'reference' => 'SD181-' . Str::upper(Str::random(8)),
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(5),
            'daily_price_snapshot' => 400,
            'total_amount' => 800,
            'status' => 'confirmed',
        ]);

        Http::fake();

        $this->postJson('/api/smartdrive/recommend', $this->validPayload($city))
            ->assertOk()
            ->assertJsonPath('total', 0);

        Http::assertNothingSent();
    }

    public function test_ai_service_failure_returns_503(): void
    {
        [$city, $agency] = $this->cityWithAgency();
        $this->availableCar($agency, $city);

        Http::fake([
            self::AI_URL . '/api/recommend' => Http::response('boom', 500),
        ]);

        $this->postJson('/api/smartdrive/recommend', $this->validPayload($city))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Le service de recommandation est momentanément indisponible. Réessayez dans quelques instants.');
    }

    public function test_ai_service_timeout_returns_503(): void
    {
        [$city, $agency] = $this->cityWithAgency();
        $this->availableCar($agency, $city);

        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->postJson('/api/smartdrive/recommend', $this->validPayload($city))
            ->assertStatus(503);
    }
}
