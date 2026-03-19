<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisCatalog;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiagnosisCatalogController extends Controller
{
    public function index()
    {
        $clinicId = auth()->user()->clinic_id;

        $catalog = DiagnosisCatalog::where('clinic_id', $clinicId)
            ->with('treatments:id,name')
            ->orderBy('code')
            ->get();

        $treatments = Treatment::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return Inertia::render('Admin/Diagnoses/Index', [
            'catalog'    => $catalog,
            'treatments' => $treatments,
            'severities' => DiagnosisCatalog::$severities,
        ]);
    }

    public function store(Request $request)
    {
        $clinicId  = auth()->user()->clinic_id;
        $validated = $this->validateEntry($request, $clinicId);

        $entry = DiagnosisCatalog::create([...$validated, 'clinic_id' => $clinicId]);
        $entry->treatments()->sync($request->treatment_ids ?? []);

        return back()->with('success', 'Diagnóstico añadido al catálogo.');
    }

    public function update(Request $request, DiagnosisCatalog $diagnosis)
    {
        abort_if($diagnosis->clinic_id !== auth()->user()->clinic_id, 403);

        $validated = $this->validateEntry($request, $diagnosis->clinic_id, $diagnosis->id);
        $diagnosis->update($validated);
        $diagnosis->treatments()->sync($request->treatment_ids ?? []);

        return back()->with('success', 'Diagnóstico actualizado.');
    }

    public function destroy(DiagnosisCatalog $diagnosis)
    {
        abort_if($diagnosis->clinic_id !== auth()->user()->clinic_id, 403);
        $diagnosis->delete();
        return back()->with('success', 'Diagnóstico eliminado del catálogo.');
    }

    private function validateEntry(Request $request, int $clinicId, ?int $ignoreId = null): array
    {
        $codeRule = "required|string|max:20|unique:diagnosis_catalog,code,{$ignoreId},id,clinic_id,{$clinicId}";

        return $request->validate([
            'code'        => $codeRule,
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:7',
            'severity'    => 'required|in:low,medium,high,critical',
            'is_active'   => 'boolean',
        ]);
    }
}
