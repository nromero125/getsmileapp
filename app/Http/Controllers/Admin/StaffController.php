<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        $clinic = auth()->user()->clinic;

        $staff = User::where('clinic_id', $clinic->id)
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(function ($member) {
                $member->future_appointments_count = in_array($member->role, ['dentist', 'admin'])
                    ? Appointment::where('dentist_id', $member->id)
                        ->where('appointment_date', '>=', now())
                        ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
                        ->count()
                    : 0;
                return $member;
            });

        $activeCount   = $staff->where('is_active', true)->count();
        $seatsIncluded = 3;
        $extraSeats    = max(0, $activeCount - $seatsIncluded);

        $activeDentists = $staff->filter(fn($u) => $u->is_active && in_array($u->role, ['dentist', 'admin']))
            ->values();

        return Inertia::render('Admin/Staff/Index', [
            'staff'          => $staff->values(),
            'activeCount'    => $activeCount,
            'seatsIncluded'  => $seatsIncluded,
            'extraSeats'     => $extraSeats,
            'onTrial'        => $clinic->onLocalTrial(),
            'activeDentists' => $activeDentists->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function store(Request $request)
    {
        $clinic = $request->user()->clinic;

        $activeCount = User::where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->count();

        // During trial: hard limit of 3 users
        if ($activeCount >= 3 && $clinic->onLocalTrial() && !$clinic->subscribed('default')) {
            return back()->withErrors([
                'limit' => 'El plan de prueba incluye hasta 3 usuarios. Activa tu suscripción para agregar usuarios adicionales ($3/usuario/mes).',
            ]);
        }

        // If subscribed and going over 3, require extra_seat price to be configured
        if ($activeCount >= 3 && $clinic->subscribed('default') && !config('cashier.price_extra_seat')) {
            return back()->withErrors([
                'limit' => 'No se ha configurado el precio de asientos adicionales. Contacta a soporte.',
            ]);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:8',
            'role'      => 'required|in:dentist,receptionist',
            'phone'     => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:255',
        ]);

        User::create([
            ...$validated,
            'clinic_id' => $clinic->id,
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        // Sync after creation so count includes new user
        SubscriptionController::syncExtraSeats($clinic->fresh());

        return back()->with('success', 'Usuario agregado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->clinic_id !== auth()->user()->clinic_id) abort(403);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'specialty'     => 'nullable|string|max:255',
            'is_active'     => 'boolean',
            'working_hours' => 'nullable|array',
        ]);

        $wasActive = $user->is_active;
        $user->update($validated);

        // Sync seats if active status changed
        if (isset($validated['is_active']) && $validated['is_active'] !== $wasActive) {
            SubscriptionController::syncExtraSeats(auth()->user()->clinic->fresh());
        }

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user)
    {
        $clinic = auth()->user()->clinic;

        if ($user->clinic_id !== $clinic->id) abort(403);
        if ($user->id === auth()->id()) return back()->withErrors(['error' => 'No puedes desactivarte a ti mismo.']);

        // Reassign future appointments if requested
        if ($request->reassign_to) {
            $reassignTo = User::where('id', $request->reassign_to)
                ->where('clinic_id', $clinic->id)
                ->where('is_active', true)
                ->firstOrFail();

            Appointment::where('dentist_id', $user->id)
                ->where('appointment_date', '>=', now())
                ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
                ->update(['dentist_id' => $reassignTo->id]);
        }

        $user->update(['is_active' => false]);

        SubscriptionController::syncExtraSeats($clinic->fresh());

        return back()->with('success', 'Usuario desactivado correctamente.');
    }
}
