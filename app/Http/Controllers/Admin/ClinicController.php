<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClinicController extends Controller
{
    public function edit()
    {
        $clinic = auth()->user()->clinic;
        return Inertia::render('Admin/Clinic/Settings', ['clinic' => $clinic]);
    }

    public function update(Request $request)
    {
        $clinic = auth()->user()->clinic;

        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string',
            'website'     => 'nullable|url',
            'tax_id'      => 'nullable|string|max:50',
            'logo'        => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($clinic->logo_path) {
                Storage::disk('public')->delete($clinic->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('clinic/logos', 'public');
        }

        unset($validated['logo']);
        $validated['name'] = $validated['clinic_name'];
        unset($validated['clinic_name']);
        $clinic->update($validated);

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
