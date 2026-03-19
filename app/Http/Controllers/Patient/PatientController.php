<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ToothDiagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $query = Patient::where('clinic_id', $clinicId);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($request->gender) {
            $query->where('gender', $request->gender);
        }

        $patients = $query->orderBy('last_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => [
                'id'         => $p->id,
                'full_name'  => $p->full_name,
                'email'      => $p->email,
                'phone'      => $p->phone,
                'dob'        => $p->date_of_birth?->format('M d, Y'),
                'age'        => $p->age,
                'gender'     => $p->gender,
                'city'       => $p->city,
                'avatar_url' => $p->avatar_url,
                'is_active'  => $p->is_active,
            ]);

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'filters'  => $request->only(['search', 'gender']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Patients/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'email'                  => 'nullable|email|max:255',
            'phone'                  => 'required|string|max:20',
            'phone_alt'              => 'nullable|string|max:20',
            'date_of_birth'          => 'nullable|date',
            'gender'                 => 'nullable|in:male,female,other',
            'address'                => 'nullable|string',
            'city'                   => 'nullable|string|max:100',
            'state'                  => 'nullable|string|max:100',
            'zip_code'               => 'nullable|string|max:20',
            'blood_type'             => 'nullable|string|max:10',
            'allergies'              => 'nullable|string',
            'medical_notes'          => 'nullable|string',
            'insurance_provider'     => 'nullable|string|max:100',
            'insurance_policy_number'=> 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone'=> 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'avatar'                 => 'nullable|image|max:2048',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $validated['clinic_id'] = $clinicId;

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $request->file('avatar')->store('patients/avatars', 'public');
        }

        unset($validated['avatar']);
        $patient = Patient::create($validated);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient)
    {
        $this->authorizeClinic($patient);

        $patient->load([
            'appointments.dentist',
            'appointments.treatments',
            'clinicalNotes.dentist',
            'invoices',
        ]);

        $recentAppointments = $patient->appointments()
            ->with(['dentist', 'treatments'])
            ->orderByDesc('appointment_date')
            ->take(10)
            ->get();

        // Collect all media across all collections
        $allMedia = $patient->getMedia('xrays')
            ->merge($patient->getMedia('photos'))
            ->merge($patient->getMedia('documents'))
            ->merge($patient->getMedia('other'))
            ->sortByDesc('created_at')
            ->map(fn ($m) => [
                'id'           => $m->id,
                'name'         => $m->name,
                'file_name'    => $m->file_name,
                'collection'   => $m->collection_name,
                'mime_type'    => $m->mime_type,
                'size'         => $m->size,
                'url'          => $m->getUrl(),
                'thumb_url'    => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : null,
                'is_image'     => str_starts_with($m->mime_type, 'image/'),
                'notes'        => $m->getCustomProperty('notes'),
                'uploader_name'=> $m->getCustomProperty('uploader_name'),
                'created_at'   => $m->created_at->format('M d, Y'),
            ]);

        // Tooth diagnoses grouped by tooth_number for the mini odontogram
        $toothDiagnoses = ToothDiagnosis::where('patient_id', $patient->id)
            ->where('clinic_id', $patient->clinic_id)
            ->with('catalog:id,name,color,code,severity')
            ->orderBy('tooth_number')
            ->orderByDesc('diagnosed_at')
            ->get()
            ->groupBy('tooth_number');

        return Inertia::render('Patients/Show', [
            'patient'             => $patient,
            'recent_appointments' => $recentAppointments,
            'tooth_diagnoses'     => $toothDiagnoses,
            'clinical_notes'      => $patient->clinicalNotes->take(5),
            'invoices'            => $patient->invoices->take(5),
            'media'               => $allMedia->values(),
        ]);
    }

    public function edit(Patient $patient)
    {
        $this->authorizeClinic($patient);
        return Inertia::render('Patients/Edit', ['patient' => $patient]);
    }

    public function update(Request $request, Patient $patient)
    {
        $this->authorizeClinic($patient);

        $validated = $request->validate([
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'email'                  => 'nullable|email|max:255',
            'phone'                  => 'required|string|max:20',
            'phone_alt'              => 'nullable|string|max:20',
            'date_of_birth'          => 'nullable|date',
            'gender'                 => 'nullable|in:male,female,other',
            'address'                => 'nullable|string',
            'city'                   => 'nullable|string|max:100',
            'state'                  => 'nullable|string|max:100',
            'zip_code'               => 'nullable|string|max:20',
            'blood_type'             => 'nullable|string|max:10',
            'allergies'              => 'nullable|string',
            'medical_notes'          => 'nullable|string',
            'insurance_provider'     => 'nullable|string|max:100',
            'insurance_policy_number'=> 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone'=> 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'avatar'                 => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($patient->avatar_path) {
                Storage::disk('public')->delete($patient->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('patients/avatars', 'public');
        }

        unset($validated['avatar']);
        $patient->update($validated);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient)
    {
        $this->authorizeClinic($patient);
        $patient->delete();
        return redirect()->route('patients.index')
            ->with('success', 'Patient deleted successfully.');
    }

    private function authorizeClinic(Patient $patient): void
    {
        if ($patient->clinic_id !== auth()->user()->clinic_id) {
            abort(403);
        }
    }
}
