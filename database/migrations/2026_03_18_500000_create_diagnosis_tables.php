<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#6B7280');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['clinic_id', 'code']);
        });

        Schema::create('diagnosis_catalog_treatment', function (Blueprint $table) {
            $table->foreignId('diagnosis_catalog_id')->constrained('diagnosis_catalog')->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->primary(['diagnosis_catalog_id', 'treatment_id']);
        });

        Schema::create('tooth_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tooth_number');
            $table->foreignId('diagnosis_catalog_id')->constrained('diagnosis_catalog');
            $table->foreignId('dentist_id')->constrained('users');
            $table->string('notes')->nullable();
            $table->timestamp('diagnosed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_diagnoses');
        Schema::dropIfExists('diagnosis_catalog_treatment');
        Schema::dropIfExists('diagnosis_catalog');
    }
};
