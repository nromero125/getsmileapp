<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ncf_sequences');

        Schema::create('ncf_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('type', 5);             // B01, B02, B04
            $table->string('prefix', 5);            // B01, B02, B04
            $table->unsignedInteger('from_number'); // Inicio del rango DGII
            $table->unsignedInteger('to_number');   // Fin del rango DGII
            $table->unsignedInteger('current_number'); // Último emitido (from_number - 1 = ninguno)
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_locked')->default(false); // true tras el primer uso
            $table->timestamps();

            $table->index(['clinic_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncf_sequences');
    }
};
