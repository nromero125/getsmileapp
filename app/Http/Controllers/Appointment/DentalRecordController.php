<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\DentalRecord;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentalRecordController extends Controller
{
    public function index(Patient $patient)
    {
        $clinicId = auth()->user()->clinic_id;
        if ($patient->clinic_id !== $clinicId) abort(403);

        $records = DentalRecord::where('patient_id', $patient->id)
            ->orderBy('tooth_number')
            ->orderByDesc('created_at')
            ->with('dentist')
            ->get()
            ->groupBy('tooth_number');

        return Inertia::render('Patients/Odontogram', [
            'patient'      => $patient,
            'records'      => $records,
            'conditions'   => DentalRecord::$conditions,
        ]);
    }

    public function store(Request $request, Patient $patient)
    {
        $clinicId = auth()->user()->clinic_id;
        if ($patient->clinic_id !== $clinicId) abort(403);

        $validated = $request->validate([
            'tooth_number'   => 'required|integer|min:1|max:32',
            'condition'      => 'required|in:healthy,cavity,crown,extraction,root_canal,implant,filling,bridge,veneer,missing,other',
            'appointment_id' => 'nullable|exists:appointments,id',
            'surface'        => 'nullable|string|max:50',
            'notes'          => 'nullable|string',
        ]);

        $record = DentalRecord::create([
            ...$validated,
            'clinic_id'  => $clinicId,
            'patient_id' => $patient->id,
            'dentist_id' => auth()->id(),
        ]);

        return back()->with('success', 'Dental record saved.');
    }

    public function update(Request $request, Patient $patient, DentalRecord $record)
    {
        $clinicId = auth()->user()->clinic_id;
        if ($record->clinic_id !== $clinicId) abort(403);

        $validated = $request->validate([
            'condition' => 'required|in:healthy,cavity,crown,extraction,root_canal,implant,filling,bridge,veneer,missing,other',
            'surface'   => 'nullable|string|max:50',
            'notes'     => 'nullable|string',
        ]);

        $record->update($validated);
        return back()->with('success', 'Record updated.');
    }

    public function destroy(Patient $patient, DentalRecord $record)
    {
        $clinicId = auth()->user()->clinic_id;
        if ($record->clinic_id !== $clinicId) abort(403);
        $record->delete();
        return back()->with('success', 'Record removed.');
    }
}
