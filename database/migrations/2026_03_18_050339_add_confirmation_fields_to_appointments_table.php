<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('confirmation_token')->nullable()->unique()->after('total_cost');
            $table->timestamp('confirmed_at')->nullable()->after('confirmation_token');
            $table->timestamp('confirmation_sent_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['confirmation_token', 'confirmed_at', 'confirmation_sent_at']);
        });
    }
};
