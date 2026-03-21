<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ncf_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('type', 5);           // B01, B02
            $table->unsignedInteger('current_number')->default(0);
            $table->unsignedInteger('max_number')->default(1000);
            $table->boolean('is_active')->default(true);
            $table->unique(['clinic_id', 'type']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncf_sequences');
    }
};
