<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone');          // número normalizado E.164
            $table->enum('direction', ['in', 'out']);
            $table->text('body')->nullable(); // texto libre entrante / descripción de plantilla saliente
            $table->string('template')->nullable(); // nombre de plantilla (solo outgoing)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'phone']);
            $table->index(['clinic_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
