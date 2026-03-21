<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('ncf', 13)->nullable()->after('invoice_number');
            $table->string('ncf_type', 5)->nullable()->after('ncf'); // B01, B02
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['ncf', 'ncf_type']);
        });
    }
};
