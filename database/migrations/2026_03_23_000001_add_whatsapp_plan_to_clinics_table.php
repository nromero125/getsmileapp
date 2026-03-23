<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('wa_plan')->nullable()->after('trial_ends_at');  // basic | standard | pro
            $table->unsignedInteger('wa_messages_quota')->default(0)->after('wa_plan');
            $table->unsignedInteger('wa_messages_used')->default(0)->after('wa_messages_quota');
            $table->timestamp('wa_messages_reset_at')->nullable()->after('wa_messages_used');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['wa_plan', 'wa_messages_quota', 'wa_messages_used', 'wa_messages_reset_at']);
        });
    }
};
