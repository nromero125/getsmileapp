<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentCategory extends Model
{
    use HasFactory;

    protected $fillable = ['clinic_id', 'name', 'color'];

    public function clinic() { return $this->belongsTo(Clinic::class); }
    public function treatments() { return $this->hasMany(Treatment::class); }
}
