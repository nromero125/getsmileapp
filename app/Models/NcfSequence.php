<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NcfSequence extends Model
{
    protected $fillable = [
        'clinic_id', 'type', 'prefix',
        'from_number', 'to_number', 'current_number',
        'expires_at', 'is_active', 'is_locked',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_locked'  => 'boolean',
        'expires_at' => 'date',
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }

    // ── Computed helpers ──────────────────────────────────────────────────

    public function getUsedAttribute(): int
    {
        return max(0, $this->current_number - $this->from_number + 1);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->to_number - $this->current_number);
    }

    public function getTotalAttribute(): int
    {
        return $this->to_number - $this->from_number + 1;
    }

    public function getUsagePctAttribute(): float
    {
        if ($this->total === 0) return 100;
        return round(($this->current_number - $this->from_number + 1) / $this->total * 100, 1);
    }

    public function getNextNcfAttribute(): string
    {
        $next = $this->current_number + 1;
        return $this->prefix . str_pad($next, 8, '0', STR_PAD_LEFT);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsExhaustedAttribute(): bool
    {
        return $this->current_number >= $this->to_number;
    }

    public function getIsNearlyExhaustedAttribute(): bool
    {
        return $this->remaining <= max(1, round($this->total * 0.1));
    }

    public function getIsNearlyExpiredAttribute(): bool
    {
        return $this->expires_at && !$this->is_expired
            && $this->expires_at->diffInDays(now()) <= 30;
    }

    // ── NCF generation (atomic) ───────────────────────────────────────────

    /**
     * Generate the next NCF for the given clinic + type.
     * Returns ['ncf' => string, 'error' => null] or ['ncf' => null, 'error' => string]
     */
    public static function generate(int $clinicId, string $type): array
    {
        $result = ['ncf' => null, 'error' => null];

        DB::transaction(function () use ($clinicId, $type, &$result) {
            $seq = static::where('clinic_id', $clinicId)
                ->where('type', $type)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $result['error'] = "No hay secuencia NCF activa para el tipo {$type}.";
                return;
            }

            // Check expiry
            if ($seq->is_expired) {
                $result['error'] = "La secuencia NCF {$type} está vencida (venció el {$seq->expires_at->format('d/m/Y')}).";
                return;
            }

            // Check if current sequence is exhausted → try auto-activate next
            if ($seq->is_exhausted) {
                $seq->update(['is_active' => false]);

                $next = static::where('clinic_id', $clinicId)
                    ->where('type', $type)
                    ->where('is_active', false)
                    ->where('is_locked', false)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString());
                    })
                    ->whereColumn('current_number', '<', 'to_number')
                    ->orderBy('from_number')
                    ->lockForUpdate()
                    ->first();

                if (!$next) {
                    $result['error'] = "La secuencia NCF {$type} está agotada y no hay secuencia siguiente disponible.";
                    Log::warning('NcfSequence:exhausted', compact('clinicId', 'type'));
                    return;
                }

                $next->update(['is_active' => true]);
                $seq = $next;
            }

            $nextNumber = $seq->current_number + 1;

            // Critical range validation
            if ($nextNumber < $seq->from_number || $nextNumber > $seq->to_number) {
                $result['error'] = "El número {$nextNumber} está fuera del rango autorizado ({$seq->from_number}-{$seq->to_number}).";
                Log::error('NcfSequence:out_of_range', compact('clinicId', 'type', 'nextNumber'));
                return;
            }

            $seq->update([
                'current_number' => $nextNumber,
                'is_locked'      => true,
            ]);

            $ncf = $seq->prefix . str_pad($nextNumber, 8, '0', STR_PAD_LEFT);

            Log::info('NcfSequence:generated', [
                'clinic_id' => $clinicId,
                'type'      => $type,
                'ncf'       => $ncf,
                'sequence'  => $seq->id,
            ]);

            $result['ncf'] = $ncf;
        });

        return $result;
    }

    // ── Validation helpers ────────────────────────────────────────────────

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'B01' => 'B01 — Crédito Fiscal',
            'B02' => 'B02 — Consumo',
            'B04' => 'B04 — Nota de Crédito (Anulación)',
            default => $type,
        };
    }

    public static function allowedTypes(): array
    {
        return ['B01', 'B02', 'B04'];
    }
}
