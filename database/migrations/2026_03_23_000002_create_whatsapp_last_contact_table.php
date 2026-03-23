<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_last_contact', function (Blueprint $table) {
            $table->string('phone')->primary();   // número normalizado E.164
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_last_contact');
    }
};
