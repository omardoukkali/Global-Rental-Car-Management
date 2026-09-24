<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calls the SmartDrive AI service (FastAPI) to score the eligible vehicles.
 */
class SmartDriveAiService
{
    /**
     * Sends the trip, the preferences and the eligible vehicles to the AI.
     * Returns null when the service answers badly or does not answer at all,
     * so the controller can send a clear message to the client.
     */
    public function recommend(array $payload): ?array
    {
        $url = rtrim(config('services.ai.url'), '/') . '/api/recommend';

        try {
            $response = Http::timeout(config('services.ai.timeout'))
                ->acceptJson()
                ->post($url, $payload);
        } catch (Throwable $error) {
            // Service down, DNS failure or timeout
            Log::warning('SmartDrive AI unreachable: ' . $error->getMessage());

            return null;
        }

        if ($response->failed()) {
            Log::warning('SmartDrive AI returned ' . $response->status());

            return null;
        }

        return $response->json();
    }
}
