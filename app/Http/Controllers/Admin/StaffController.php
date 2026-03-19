<?php

namespace App\Http\Controllers\Admin;

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
            'clinic_id' => auth()->user()->clinic_id,
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return back()->with('success', 'Staff member added.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->clinic_id !== auth()->user()->clinic_id) abort(403);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'specialty'   => 'nullable|string|max:255',
            'is_active'   => 'boolean',
            'working_hours' => 'nullable|array',
        ]);

        $user->update($validated);
        return back()->with('success', 'Staff updated.');
    }

    public function destroy(User $user)
    {
        if ($user->clinic_id !== auth()->user()->clinic_id) abort(403);
        if ($user->id === auth()->id()) return back()->withErrors(['error' => 'Cannot delete yourself.']);

        $user->update(['is_active' => false]);
        return back()->with('success', 'Staff member deactivated.');
    }
}
