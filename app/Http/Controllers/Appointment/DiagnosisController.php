<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisCatalog;
use App\Models\Patient;
use App\Models\ToothDiagnosis;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiagnosisController extends Controller
{
    public function index(Patient $patient)
    {
        $clinicId = auth()->user()->clinic_id;
        if ($patient->clinic_id !== $clinicId) abort(403);

        $catalog = DiagnosisCatalog::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->with('treatments:id,name')
            ->orderBy('code')
            ->get();

        $diagnoses = ToothDiagnosis::where('patient_id', $patient->id)
            ->where('clinic_id', $clinicId)
            ->with(['catalog', 'dentist:id,name'])
            ->orderBy('tooth_number')
            ->orderByDesc('diagnosed_at')
            ->get()
            ->groupBy('tooth_number');

        return Inertia::render('Patients/Odontogram', [
            'patient'   => $patient,
            'catalog'   => $catalog,
            'diagnoses' => $diagnoses,
        ]);
    }

    public function store(Request $request, Patient $patient)
    {
        $clinicId = auth()->user()->clinic_id;
        if ($patient->clinic_id !== $clinicId) abort(403);

        $validated = $request->validate([
            'tooth_numbers'        => 'required|array|min:1',
            'tooth_numbers.*'      => 'integer|min:1|max:32',
            'diagnosis_catalog_id' => 'required|exists:diagnosis_catalog,id',
            'notes'                => 'nullable|string|max:500',
            'diagnosed_at'         => 'nullable|date',
        ]);

        // Verify catalog belongs to same clinic
        DiagnosisCatalog::where('id', $validated['diagnosis_catalog_id'])
            ->where('clinic_id', $clinicId)
            ->firstOrFail();

        $now = $validated['diagnosed_at'] ?? now();

        foreach ($validated['tooth_numbers'] as $toothNumber) {
            ToothDiagnosis::create([
                'clinic_id'            => $clinicId,
                'patient_id'           => $patient->id,
                'tooth_number'         => $toothNumber,
                'diagnosis_catalog_id' => $validated['diagnosis_catalog_id'],
                'dentist_id'           => auth()->id(),
                'notes'                => $validated['notes'] ?? null,
                'diagnosed_at'         => $now,
            ]);
        }

        $count = count($validated['tooth_numbers']);
        return back()->with('success', $count === 1 ? 'Diagnóstico registrado.' : "{$count} diagnósticos registrados.");
    }

    public function update(Request $request, Patient $patient, ToothDiagnosis $diagnosis)
    {
        $clinicId = auth()->user()->clinic_id;
        if ($diagnosis->clinic_id !== $clinicId) abort(403);

        $validated = $request->validate([
            'notes'        => 'nullable|string|max:500',
            'diagnosed_at' => 'nullable|date',
        ]);

        $diagnosis->update($validated);
        return back()->with('success', 'Diagnóstico actualizado.');
    }

    public function destroy(Patient $patient, ToothDiagnosis $diagnosis)
    {
        $clinicId = auth()->user()->clinic_id;
        if ($diagnosis->clinic_id !== $clinicId) abort(403);

        $diagnosis->delete();
        return back()->with('success', 'Diagnóstico eliminado.');
    }
}
