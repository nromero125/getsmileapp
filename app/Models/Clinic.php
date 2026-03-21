<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Paddle\Billable;
use App\Models\DiagnosisCatalog;
use App\Models\Treatment;
use App\Models\TreatmentCategory;

class Clinic extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address',
        'logo_path', 'website', 'tax_id', 'settings', 'is_active', 'trial_ends_at',
    ];

    protected $appends = ['logo_url'];

    protected $casts = [
        'settings'      => 'array',
        'is_active'     => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    public function onLocalTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasActiveAccess(): bool
    {
        return $this->onLocalTrial() || $this->subscribed('default');
    }

    public function activeUserCount(): int
    {
        return $this->users()->where('is_active', true)->count();
    }

    public function extraSeatCount(): int
    {
        return max(0, $this->activeUserCount() - 3);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($clinic) {
            if (empty($clinic->slug)) {
                $clinic->slug = Str::slug($clinic->name);
            }
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function seedDefaultCatalog(): void
    {
        $categories = [
            ['name' => 'Preventiva',   'color' => '#10B981'],
            ['name' => 'Restaurativa', 'color' => '#3B82F6'],
            ['name' => 'Ortodoncia',   'color' => '#8B5CF6'],
            ['name' => 'Cirugía Oral', 'color' => '#EF4444'],
            ['name' => 'Endodoncia',   'color' => '#F59E0B'],
            ['name' => 'Estética',     'color' => '#EC4899'],
            ['name' => 'Periodoncia',  'color' => '#06B6D4'],
        ];

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[$cat['name']] = TreatmentCategory::firstOrCreate(
                ['clinic_id' => $this->id, 'name' => $cat['name']],
                ['color' => $cat['color']]
            );
        }

        $treatments = [
            // Preventiva
            ['cat' => 'Preventiva',   'name' => 'Limpieza Dental',                    'price' => 2500,  'duration' => 60],
            ['cat' => 'Preventiva',   'name' => 'Examen Dental',                      'price' => 1500,  'duration' => 30],
            ['cat' => 'Preventiva',   'name' => 'Radiografía Dental (Completa)',       'price' => 2000,  'duration' => 30],
            ['cat' => 'Preventiva',   'name' => 'Tratamiento de Flúor',               'price' => 800,   'duration' => 15],
            ['cat' => 'Preventiva',   'name' => 'Sellantes (por diente)',              'price' => 1200,  'duration' => 15],
            // Restaurativa
            ['cat' => 'Restaurativa', 'name' => 'Resina Compuesta',                   'price' => 3500,  'duration' => 45],
            ['cat' => 'Restaurativa', 'name' => 'Amalgama',                           'price' => 2500,  'duration' => 45],
            ['cat' => 'Restaurativa', 'name' => 'Corona Dental (PFM)',                'price' => 18000, 'duration' => 90],
            ['cat' => 'Restaurativa', 'name' => 'Corona Dental (Zirconia)',           'price' => 25000, 'duration' => 90],
            ['cat' => 'Restaurativa', 'name' => 'Puente Dental (3 piezas)',           'price' => 50000, 'duration' => 120],
            ['cat' => 'Restaurativa', 'name' => 'Prótesis Dental (Completa)',         'price' => 35000, 'duration' => 90],
            // Ortodoncia
            ['cat' => 'Ortodoncia',   'name' => 'Brackets Tradicionales',             'price' => 45000, 'duration' => 60],
            ['cat' => 'Ortodoncia',   'name' => 'Alineadores Transparentes',          'price' => 80000, 'duration' => 60],
            ['cat' => 'Ortodoncia',   'name' => 'Retenedor',                          'price' => 8000,  'duration' => 30],
            // Cirugía Oral
            ['cat' => 'Cirugía Oral', 'name' => 'Extracción Simple',                  'price' => 2000,  'duration' => 30],
            ['cat' => 'Cirugía Oral', 'name' => 'Extracción de Muela del Juicio',     'price' => 6000,  'duration' => 60],
            ['cat' => 'Cirugía Oral', 'name' => 'Implante Dental',                    'price' => 60000, 'duration' => 90],
            // Endodoncia
            ['cat' => 'Endodoncia',   'name' => 'Tratamiento de Conducto (1 conducto)', 'price' => 8000,  'duration' => 90],
            ['cat' => 'Endodoncia',   'name' => 'Tratamiento de Conducto (3 conductos)', 'price' => 15000, 'duration' => 120],
            // Estética
            ['cat' => 'Estética',     'name' => 'Blanqueamiento Dental (Consultorio)', 'price' => 8000,  'duration' => 90],
            ['cat' => 'Estética',     'name' => 'Carilla de Porcelana',               'price' => 20000, 'duration' => 90],
            ['cat' => 'Estética',     'name' => 'Diseño de Sonrisa',                  'price' => 80000, 'duration' => 120],
            // Periodoncia
            ['cat' => 'Periodoncia',  'name' => 'Limpieza Profunda (RAR)',             'price' => 5000,  'duration' => 60],
            ['cat' => 'Periodoncia',  'name' => 'Cirugía de Encía',                   'price' => 20000, 'duration' => 90],
        ];

        foreach ($treatments as $t) {
            Treatment::firstOrCreate(
                ['clinic_id' => $this->id, 'name' => $t['name']],
                [
                    'treatment_category_id' => $createdCategories[$t['cat']]->id,
                    'default_price'         => $t['price'],
                    'duration_minutes'      => $t['duration'],
                    'is_active'             => true,
                ]
            );
        }

        $diagnoses = [
            ['code' => 'CAR',   'name' => 'Caries',                'description' => 'Lesión cariosa activa',                    'color' => '#EF4444', 'severity' => 'medium'],
            ['code' => 'CAR-P', 'name' => 'Caries profunda',       'description' => 'Caries con compromiso pulpar inminente',    'color' => '#DC2626', 'severity' => 'high'],
            ['code' => 'FRAC',  'name' => 'Fractura',              'description' => 'Fractura coronaria o radicular',            'color' => '#F97316', 'severity' => 'high'],
            ['code' => 'PERIO', 'name' => 'Periodontitis',         'description' => 'Enfermedad periodontal activa',             'color' => '#8B5CF6', 'severity' => 'high'],
            ['code' => 'GING',  'name' => 'Gingivitis',            'description' => 'Inflamación gingival',                     'color' => '#A78BFA', 'severity' => 'low'],
            ['code' => 'PROT',  'name' => 'Corona protésica',      'description' => 'Pieza con corona protésica',               'color' => '#F59E0B', 'severity' => 'low'],
            ['code' => 'OBT',   'name' => 'Obturación',            'description' => 'Restauración presente',                    'color' => '#3B82F6', 'severity' => 'low'],
            ['code' => 'EXTR',  'name' => 'Extracción indicada',   'description' => 'Pieza con indicación de extracción',       'color' => '#6B7280', 'severity' => 'critical'],
            ['code' => 'AUS',   'name' => 'Ausente',               'description' => 'Pieza dental ausente',                     'color' => '#1F2937', 'severity' => 'medium'],
            ['code' => 'TCR',   'name' => 'Tratamiento de conducto', 'description' => 'Pieza con tratamiento endodóntico',       'color' => '#7C3AED', 'severity' => 'medium'],
            ['code' => 'IMP',   'name' => 'Implante',              'description' => 'Pieza reemplazada por implante',           'color' => '#0EA5E9', 'severity' => 'low'],
            ['code' => 'PUEN',  'name' => 'Puente',                'description' => 'Pieza incluida en puente protésico',       'color' => '#06B6D4', 'severity' => 'low'],
            ['code' => 'FISU',  'name' => 'Fisura / Grieta',       'description' => 'Fisura o grieta en esmalte',               'color' => '#FB923C', 'severity' => 'medium'],
            ['code' => 'SENS',  'name' => 'Sensibilidad',          'description' => 'Hipersensibilidad dentinaria',             'color' => '#FCD34D', 'severity' => 'low'],
            ['code' => 'REAB',  'name' => 'Reabsorción radicular', 'description' => 'Reabsorción radicular interna o externa',  'color' => '#E879F9', 'severity' => 'high'],
            ['code' => 'SANO',  'name' => 'Sano',                  'description' => 'Pieza sin alteraciones',                   'color' => '#10B981', 'severity' => 'low'],
        ];

        foreach ($diagnoses as $d) {
            DiagnosisCatalog::firstOrCreate(
                ['clinic_id' => $this->id, 'code' => $d['code']],
                [...$d, 'clinic_id' => $this->id]
            );
        }
    }

    public function users() { return $this->hasMany(User::class); }
    public function patients() { return $this->hasMany(Patient::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function treatments() { return $this->hasMany(Treatment::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function dentists() { return $this->hasMany(User::class)->where('role', 'dentist'); }
}
