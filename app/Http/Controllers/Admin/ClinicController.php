<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NcfSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClinicController extends Controller
{
    public function edit()
    {
        $clinic = auth()->user()->clinic;

        $ncfSequences = NcfSequence::where('clinic_id', $clinic->id)
            ->orderBy('type')
            ->orderBy('from_number')
            ->get()
            ->map(fn($s) => array_merge($s->toArray(), [
                'remaining'          => $s->remaining,
                'total'              => $s->total,
                'usage_pct'          => $s->usage_pct,
                'next_ncf'           => $s->next_ncf,
                'is_expired'         => $s->is_expired,
                'is_exhausted'       => $s->is_exhausted,
                'is_nearly_exhausted'=> $s->is_nearly_exhausted,
                'is_nearly_expired'  => $s->is_nearly_expired,
            ]));

        return Inertia::render('Admin/Clinic/Settings', [
            'clinic'       => $clinic,
            'ncfSequences' => $ncfSequences,
        ]);
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

    // ── NCF Sequences CRUD ────────────────────────────────────────────────

    public function storeNcfSequence(Request $request)
    {
        $clinic = auth()->user()->clinic;

        $validated = $request->validate([
            'type'           => 'required|in:B01,B02,B04',
            'from_number'    => 'required|integer|min:1',
            'to_number'      => 'required|integer|min:1|gte:from_number',
            'expires_at'     => 'nullable|date|after:today',
            'is_active'      => 'boolean',
        ]);

        // Only one active per type
        if ($validated['is_active'] ?? false) {
            $alreadyActive = NcfSequence::where('clinic_id', $clinic->id)
                ->where('type', $validated['type'])
                ->where('is_active', true)
                ->exists();

            if ($alreadyActive) {
                return back()->withErrors(['is_active' => 'Ya existe una secuencia activa para este tipo. Desactívala primero.']);
            }
        }

        $seq = NcfSequence::create([
            'clinic_id'      => $clinic->id,
            'type'           => $validated['type'],
            'prefix'         => $validated['type'],
            'from_number'    => $validated['from_number'],
            'to_number'      => $validated['to_number'],
            'current_number' => $validated['from_number'] - 1,
            'expires_at'     => $validated['expires_at'] ?? null,
            'is_active'      => $validated['is_active'] ?? false,
            'is_locked'      => false,
        ]);

        Log::info('NcfSequence:created', [
            'clinic_id' => $clinic->id,
            'user_id'   => auth()->id(),
            'sequence'  => $seq->id,
            'type'      => $seq->type,
            'range'     => "{$seq->from_number}-{$seq->to_number}",
        ]);

        return back()->with('success', "Secuencia {$seq->type} ({$seq->from_number}-{$seq->to_number}) creada correctamente.");
    }

    public function updateNcfSequence(Request $request, NcfSequence $sequence)
    {
        $this->authorizeSequence($sequence);

        $rules = [
            'expires_at' => 'nullable|date',
            'is_active'  => 'boolean',
        ];

        // Locked fields (only editable before first use)
        if (!$sequence->is_locked) {
            $rules['from_number']    = 'required|integer|min:1';
            $rules['to_number']      = 'required|integer|min:1|gte:from_number';
            $rules['current_number'] = 'required|integer|gte:0';
        }

        $validated = $request->validate($rules);

        // Validate current_number in range (only if not locked)
        if (!$sequence->is_locked && isset($validated['current_number'])) {
            $from = $validated['from_number'] ?? $sequence->from_number;
            $to   = $validated['to_number']   ?? $sequence->to_number;
            if ($validated['current_number'] > $to) {
                return back()->withErrors(['current_number' => "El número actual no puede superar el número final ({$to})."]);
            }
        }

        // Only one active per type
        if (($validated['is_active'] ?? false) && !$sequence->is_active) {
            $alreadyActive = NcfSequence::where('clinic_id', $sequence->clinic_id)
                ->where('type', $sequence->type)
                ->where('is_active', true)
                ->where('id', '!=', $sequence->id)
                ->exists();

            if ($alreadyActive) {
                return back()->withErrors(['is_active' => 'Ya existe una secuencia activa para este tipo. Desactívala primero.']);
            }
        }

        $before = $sequence->toArray();
        $sequence->update($validated);

        Log::info('NcfSequence:updated', [
            'clinic_id' => $sequence->clinic_id,
            'user_id'   => auth()->id(),
            'sequence'  => $sequence->id,
            'before'    => $before,
            'after'     => $sequence->fresh()->toArray(),
        ]);

        return back()->with('success', 'Secuencia NCF actualizada.');
    }

    public function destroyNcfSequence(NcfSequence $sequence)
    {
        $this->authorizeSequence($sequence);

        if ($sequence->is_locked) {
            return back()->withErrors(['error' => 'No se puede eliminar una secuencia que ya ha emitido comprobantes.']);
        }

        Log::info('NcfSequence:deleted', [
            'clinic_id' => $sequence->clinic_id,
            'user_id'   => auth()->id(),
            'sequence'  => $sequence->id,
            'type'      => $sequence->type,
            'range'     => "{$sequence->from_number}-{$sequence->to_number}",
        ]);

        $sequence->delete();

        return back()->with('success', 'Secuencia eliminada.');
    }

    private function authorizeSequence(NcfSequence $sequence): void
    {
        if ($sequence->clinic_id !== auth()->user()->clinic_id) {
            abort(403);
        }
    }
}
