<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    public function index()
    {
        $clinicId = auth()->user()->clinic_id;

        $categories = TreatmentCategory::where('clinic_id', $clinicId)
            ->withCount('treatments')
            ->orderBy('name')
            ->get();

        $treatments = Treatment::where('clinic_id', $clinicId)
            ->with('category')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Treatments/Index', [
            'categories' => $categories,
            'treatments' => $treatments,
        ]);
    }

    public function store(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'treatment_category_id' => 'nullable|exists:treatment_categories,id',
            'default_price'         => 'required|numeric|min:0',
            'duration_minutes'      => 'required|integer|min:1',
            'description'           => 'nullable|string',
            'color'                 => 'nullable|string|max:7',
            'is_active'             => 'boolean',
        ]);

        Treatment::create([...$validated, 'clinic_id' => $clinicId]);

        return back()->with('success', 'Tratamiento creado.');
    }

    public function update(Request $request, Treatment $treatment)
    {
        $this->authorizeClinic($treatment);

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'treatment_category_id' => 'nullable|exists:treatment_categories,id',
            'default_price'         => 'required|numeric|min:0',
            'duration_minutes'      => 'required|integer|min:1',
            'description'           => 'nullable|string',
            'color'                 => 'nullable|string|max:7',
            'is_active'             => 'boolean',
        ]);

        $treatment->update($validated);

        return back()->with('success', 'Tratamiento actualizado.');
    }

    public function destroy(Treatment $treatment)
    {
        $this->authorizeClinic($treatment);
        $treatment->delete();
        return back()->with('success', 'Tratamiento eliminado.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        TreatmentCategory::create([...$validated, 'clinic_id' => auth()->user()->clinic_id]);

        return back()->with('success', 'Categoría creada.');
    }

    public function destroyCategory(TreatmentCategory $category)
    {
        abort_if($category->clinic_id !== auth()->user()->clinic_id, 403);
        $category->delete();
        return back()->with('success', 'Categoría eliminada.');
    }

    private function authorizeClinic(Treatment $treatment): void
    {
        abort_if($treatment->clinic_id !== auth()->user()->clinic_id, 403);
    }
}
