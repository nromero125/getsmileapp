<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->cascadeOnDelete();
            $table->integer('tooth_number'); // 1-32 (Universal Numbering System)
            $table->enum('condition', ['healthy', 'cavity', 'crown', 'extraction', 'root_canal', 'implant', 'filling', 'bridge', 'veneer', 'missing', 'other']);
            $table->string('surface')->nullable(); // mesial, distal, occlusal, buccal, lingual
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'tooth_number']);
        });

        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->cascadeOnDelete();
            $table->text('subjective')->nullable(); // Patient complaints
            $table->text('objective')->nullable();  // Clinical findings
            $table->text('assessment')->nullable(); // Diagnosis
            $table->text('plan')->nullable();       // Treatment plan
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_notes');
        Schema::dropIfExists('dental_records');
    }
};
