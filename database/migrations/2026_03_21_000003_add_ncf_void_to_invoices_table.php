<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('ncf_void', 13)->nullable()->after('ncf_type');
            $table->timestamp('voided_at')->nullable()->after('ncf_void');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['ncf_void', 'voided_at']);
        });
    }
};
