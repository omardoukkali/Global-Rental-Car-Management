<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Car;
use App\Models\Agency;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get data from DB
        $john  = User::where('email', 'client@test.ma')->first();
        $sara  = User::where('email', 'sara@test.ma')->first();

        $corolla = Car::where('plate_number', 'A-12345-B')->first();
        $duster  = Car::where('plate_number', 'A-67890-B')->first();
        $clio    = Car::where('plate_number', 'M-11111-C')->first();

        $automaroc     = Agency::where('slug', 'automaroc')->first();
        $marrakechcars = Agency::where('slug', 'marrakech-cars')->first();

        // Reservation 1 — completed
        Reservation::create([
            'id'                    => Str::uuid(),
            'client_id'             => $john->id,
            'car_id'                => $corolla->id,
            'agency_id'             => $automaroc->id,
            'reference_number'      => 'RES-' . strtoupper(Str::random(8)),
            'start_date'            => '2026-05-01 10:00:00',
            'end_date'              => '2026-05-05 10:00:00',
            'price_per_day_snapshot'=> 350.00,
            'total_amount'          => 1400.00,
            'commission_amount'     => 210.00,
            'agency_earning'        => 1190.00,
            'status'                => 'completed',
            'cancellation_reason'   => null,
            'cancelled_at'          => null,
            'completed_at'          => '2026-05-05 10:00:00',
        ]);

        // Reservation 2 — confirmed
        Reservation::create([
            'id'                    => Str::uuid(),
            'client_id'             => $sara->id,
            'car_id'                => $duster->id,
            'agency_id'             => $automaroc->id,
            'reference_number'      => 'RES-' . strtoupper(Str::random(8)),
            'start_date'            => '2026-06-10 09:00:00',
            'end_date'              => '2026-06-15 09:00:00',
            'price_per_day_snapshot'=> 450.00,
            'total_amount'          => 2250.00,
            'commission_amount'     => 337.50,
            'agency_earning'        => 1912.50,
            'status'                => 'confirmed',
            'cancellation_reason'   => null,
            'cancelled_at'          => null,
            'completed_at'          => null,
        ]);

        // Reservation 3 — cancelled
        Reservation::create([
            'id'                    => Str::uuid(),
            'client_id'             => $john->id,
            'car_id'                => $clio->id,
            'agency_id'             => $marrakechcars->id,
            'reference_number'      => 'RES-' . strtoupper(Str::random(8)),
            'start_date'            => '2026-05-20 08:00:00',
            'end_date'              => '2026-05-23 08:00:00',
            'price_per_day_snapshot'=> 250.00,
            'total_amount'          => 750.00,
            'commission_amount'     => 112.50,
            'agency_earning'        => 637.50,
            'status'                => 'cancelled',
            'cancellation_reason'   => 'Changed travel plans',
            'cancelled_at'          => '2026-05-18 14:00:00',
            'completed_at'          => null,
        ]);
    }
}
