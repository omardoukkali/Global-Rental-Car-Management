<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WebAuthController extends Controller
{
    // Render Login Page
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectUserByRole(Auth::user());
        }
        return Inertia::render('Auth/Login');
    }

    // Render Register Page
    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectUserByRole(Auth::user());
        }
        
        $cities = City::where('is_active', true)->orderBy('name')->get();
        return Inertia::render('Auth/Register', [
            'cities' => $cities
        ]);
    }

    // Register Client (Web session)
    public function registerClient(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8'
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => 'client',
            'status' => 'active'
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('landing')->with('success', 'Welcome! Account created successfully.');
    }

    // Register Agency Owner (Web session, pending approval)
    public function registerAgency(Request $request)
    {
        $validated = $request->validate([
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'phone'       => 'required|string|max:20',
            'agency_name' => 'required|string|max:255',
            'agency_city' => 'required|uuid|exists:cities,id',
            'address'     => 'required|string|max:255',
            'agency_phone'=> 'required|string|max:20',
        ]);

        // Create user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => $validated['password'],
            'phone'      => $validated['phone'],
            'role'       => 'agency_owner',
            'status'     => 'active',
        ]);

        // Create agency (pending approval)
        Agency::create([
            'owner_id' => $user->id,
            'city_id'  => $validated['agency_city'],
            'name'     => $validated['agency_name'],
            'slug'     => Str::slug($validated['agency_name']),
            'address'  => $validated['address'],
            'phone'    => $validated['agency_phone'],
            'status'   => 'pending',
        ]);

        return redirect()->route('login')->with('success', 'Agency registration submitted! Waiting for administrator review.');
    }

    // Web Session Login
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($validated)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();
        if ($user->status === 'blocked') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account has been blocked. Please contact admin.',
            ]);
        }
        if ($user->status === 'pending') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account is pending approval.',
            ]);
        }

        $request->session()->regenerate();

        return $this->redirectUserByRole($user)->with('success', 'Logged in successfully!');
    }

    // Web Session Logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('success', 'Logged out successfully.');
    }

    // Redirect handler based on user role
    protected function redirectUserByRole($user)
    {
        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'agency_owner' => redirect()->route('agency.dashboard'),
            'client' => redirect()->route('landing'),
            default => redirect()->route('landing'),
        };
    }
}
