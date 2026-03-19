<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosisCatalog extends Model
{
    use HasFactory;

    protected $table = 'diagnosis_catalog';

    protected $fillable = [
        'clinic_id', 'code', 'name', 'description', 'color', 'severity', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static array $severities = [
        'low'      => ['label' => 'Baja',     'color' => '#10B981'],
        'medium'   => ['label' => 'Media',    'color' => '#F59E0B'],
        'high'     => ['label' => 'Alta',     'color' => '#EF4444'],
        'critical' => ['label' => 'Crítica',  'color' => '#7C3AED'],
    ];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function toothDiagnoses() { return $this->hasMany(ToothDiagnosis::class); }
    public function treatments() { return $this->belongsToMany(Treatment::class, 'diagnosis_catalog_treatment'); }
}
