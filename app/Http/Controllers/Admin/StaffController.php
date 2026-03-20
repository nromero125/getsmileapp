<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::where('clinic_id', auth()->user()->clinic_id)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Staff/Index', ['staff' => $staff]);
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

        // Sync extra seats in Paddle if going over 3
        if ($activeCount >= 3) {
            SubscriptionController::syncExtraSeats($clinic->fresh());
        }

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

        $user->update($validated);
        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $clinic = auth()->user()->clinic;

        if ($user->clinic_id !== $clinic->id) abort(403);
        if ($user->id === auth()->id()) return back()->withErrors(['error' => 'No puedes desactivarte a ti mismo.']);

        $user->update(['is_active' => false]);

        // Recalculate extra seats after deactivation
        SubscriptionController::syncExtraSeats($clinic->fresh());

        return back()->with('success', 'Usuario desactivado correctamente.');
    }
}
