<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Agency;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // ── GET /api/admin/dashboard
    public function dashboard()
    {
        return response()->json([
            'stats' => [
                'total_users'        => User::where('role', 'client')->count(),
                'total_agencies'     => Agency::count(),
                'pending_agencies'   => Agency::where('status', 'pending')->count(),
                'total_cars'         => Car::count(),
                'total_reservations' => Reservation::count(),
                'active_reservations'=> Reservation::where('status', 'confirmed')->count(),
                'total_revenue'      => Payment::where('status', 'released')->sum('commission_amount'),
                'pending_payments'   => Payment::where('status', 'paid')->count(),
                'total_reviews'      => Review::count(),
            ]
        ]);
    }

    // ── GET /api/admin/users
    public function users(Request $request)
    {
        $users = User::with('agency')
            ->when($request->role,   fn($q) => $q->where('role', $request->role))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where(function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name',  'like', '%' . $request->search . '%')
                  ->orWhere('email',      'like', '%' . $request->search . '%');
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return UserResource::collection($users);
    }

    // ── PUT /api/admin/users/{user}/suspend
    public function suspendUser(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Cannot suspend an admin'
            ], 422);
        }

        $user->update(['status' => 'blocked']);

        return response()->json([
            'message' => 'User suspended successfully',
            'user'    => new UserResource($user),
        ]);
    }

    // ── PUT /api/admin/users/{user}/activate
    public function activateUser(User $user)
    {
        $user->update(['status' => 'active']);

        return response()->json([
            'message' => 'User activated successfully',
            'user'    => new UserResource($user),
        ]);
    }

    // ── DELETE /api/admin/users/{user}
    public function deleteUser(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Cannot delete an admin'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
