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
        // Get Clients
        $john   = User::where('email', 'client@test.ma')->first();
        $sara   = User::where('email', 'sara@test.ma')->first();
        $karim  = User::where('email', 'karim@test.ma')->first();
        $fatima = User::where('email', 'fatima@test.ma')->first();

        // Get Cars
        $cclass  = Car::where('plate_number', 'A-10001-B')->first(); // AutoMaroc 800/d
        $corolla = Car::where('plate_number', 'A-10003-B')->first(); // AutoMaroc 400/d
        $duster  = Car::where('plate_number', 'M-20001-C')->first(); // Oasis 350/d
        $mustang = Car::where('plate_number', 'T-30003-D')->first(); // Tanger 1100/d
        $porsche = Car::where('plate_number', 'T-30001-D')->first(); // Tanger 2500/d

        // Get Agencies
        $automaroc = Agency::where('slug', 'automaroc-premium')->first();
        $oasis     = Agency::where('slug', 'oasis-drive-marrakech')->first();
        $tanger    = Agency::where('slug', 'tanger-exotics')->first();

        // Reservation 1 — Completed (John, Corolla, 4 days)
        Reservation::create([
            'id'                    => Str::uuid(),
            'client_id'             => $john->id,
            'car_id'                => $corolla->id,
            'agency_id'             => $automaroc->id,
            'reference_number'      => 'RES-' . strtoupper(Str::random(8)),
            'start_date'            => '2026-05-01 10:00:00',
            'end_date'              => '2026-05-05 10:00:00',
            'price_per_day_snapshot'=> 400.00,
            'total_amount'          => 1600.00,
            'commission_amount'     => 240.00,
            'agency_earning'        => 1360.00,
            'status'                => 'completed',
            'cancellation_reason'   => null,
            'cancelled_at'          => null,
            'completed_at'          => '2026-05-05 10:00:00',
        ]);

        // Reservation 2 — Confirmed (Sara, Duster, 5 days)
        Reservation::create([
            'id'                    => Str::uuid(),
            'client_id'             => $sara->id,
            'car_id'                => $duster->id,
            'agency_id'             => $oasis->id,
            'reference_number'      => 'RES-' . strtoupper(Str::random(8)),
            'start_date'            => '2026-08-10 09:00:00',
            'end_date'              => '2026-08-15 09:00:00',
            'price_per_day_snapshot'=> 350.00,
            'total_amount'          => 1750.00,
            'commission_amount'     => 262.50,
            'agency_earning'        => 1487.50,
            'status'                => 'confirmed',
            'cancellation_reason'   => null,
            'cancelled_at'          => null,
            'completed_at'          => null,
        ]);

        // Reservation 3 — Cancelled (Karim, C-Class, 2 days)
        Reservation::create([
            'id'                    => Str::uuid(),
            'client_id'             => $karim->id,
            'car_id'                => $cclass->id,
            'agency_id'             => $automaroc->id,
            'reference_number'      => 'RES-' . strtoupper(Str::random(8)),
            'start_date'            => '2026-06-20 08:00:00',
            'end_date'              => '2026-06-22 08:00:00',
            'price_per_day_snapshot'=> 800.00,
            'total_amount'          => 1600.00,
            'commission_amount'     => 240.00,
            'agency_earning'        => 1360.00,
            'status'                => 'cancelled',
            'cancellation_reason'   => 'Flight got delayed',
            'cancelled_at'          => '2026-06-18 14:00:00',
            'completed_at'          => null,
        ]);
        
        // Reservation 4 — Completed (Fatima, Mustang, 3 days)
        Reservation::create([
            'id'                    => Str::uuid(),
            'client_id'             => $fatima->id,
            'car_id'                => $mustang->id,
            'agency_id'             => $tanger->id,
            'reference_number'      => 'RES-' . strtoupper(Str::random(8)),
            'start_date'            => '2026-04-10 12:00:00',
            'end_date'              => '2026-04-13 12:00:00',
            'price_per_day_snapshot'=> 1100.00,
            'total_amount'          => 3300.00,
            'commission_amount'     => 495.00,
            'agency_earning'        => 2805.00,
            'status'                => 'completed',
            'cancellation_reason'   => null,
            'cancelled_at'          => null,
            'completed_at'          => '2026-04-13 12:00:00',
        ]);
        
        // Reservation 5 — Pending (John, Porsche, 1 day)
        Reservation::create([
            'id'                    => Str::uuid(),
            'client_id'             => $john->id,
            'car_id'                => $porsche->id,
            'agency_id'             => $tanger->id,
            'reference_number'      => 'RES-' . strtoupper(Str::random(8)),
            'start_date'            => '2026-09-01 10:00:00',
            'end_date'              => '2026-09-02 10:00:00',
            'price_per_day_snapshot'=> 2500.00,
            'total_amount'          => 2500.00,
            'commission_amount'     => 375.00,
            'agency_earning'        => 2125.00,
            'status'                => 'pending',
            'cancellation_reason'   => null,
            'cancelled_at'          => null,
            'completed_at'          => null,
        ]);
    }
}
