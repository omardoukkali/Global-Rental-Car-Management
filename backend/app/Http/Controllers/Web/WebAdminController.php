<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WebAdminController extends Controller
{
    // ── GET /admin/dashboard
    public function index(Request $request)
    {
        $stats = [
            'total_users'        => User::count(),
            'total_agencies'     => Agency::count(),
            'total_reservations' => Reservation::count(),
            'pending_agencies'   => Agency::where('status', 'pending')->count(),
        ];

        // Fetch agencies with their owners
        $agencies = Agency::with('owner')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END") // Surface pending first
            ->latest()
            ->paginate(10, ['*'], 'agencies_page');

        // Fetch users except admins
        $users = User::where('role', '!=', 'admin')
            ->latest()
            ->paginate(10, ['*'], 'users_page');

        return Inertia::render('Admin/Dashboard', [
            'stats'    => $stats,
            'agencies' => $agencies,
            'users'    => $users,
        ]);
    }

    // ── POST /admin/agencies/{agency}/approve
    public function approveAgency(Request $request, Agency $agency)
    {
        if ($agency->owner && $agency->owner->status === 'blocked') {
            return back()->with('error', 'Reactivate the owner account before approving this agency.');
        }
        $agency->update(['status' => 'approved']);
        return back()->with('success', "Agency '{$agency->name}' has been approved.");
    }

    // ── POST /admin/agencies/{agency}/reject
    public function rejectAgency(Request $request, Agency $agency)
    {
        $agency->update(['status' => 'rejected']);
        return back()->with('success', "Agency '{$agency->name}' has been rejected.");
    }

    // ── POST /admin/users/{user}/suspend
    public function suspendUser(Request $request, User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot suspend an admin user.');
        }

        DB::transaction(function () use ($user) {
            $user->update(['status' => 'blocked']);

            if ($user->role === 'agency_owner' && $user->agency) {
                // Cascade suspension to the agency, removing their cars from public view immediately.
                $user->agency->update(['status' => 'rejected']);
            }
        });

        return back()->with('success', "User {$user->first_name} {$user->last_name} has been suspended.");
    }

    // ── POST /admin/users/{user}/activate
    public function activateUser(Request $request, User $user)
    {
        $user->update(['status' => 'active']);
        
        // Note: Intentionally NOT auto-restoring agency status. 
        // Admin must explicitly re-approve the agency if they want it live again.
        
        return back()->with('success', "User {$user->first_name} {$user->last_name} has been activated.");
    }
}
