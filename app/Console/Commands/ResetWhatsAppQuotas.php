<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use Illuminate\Console\Command;

class ResetWhatsAppQuotas extends Command
{
    protected $signature   = 'whatsapp:reset-quotas';
    protected $description = 'Reset monthly WhatsApp message usage counters for all clinics with an active plan.';

    public function handle(): int
    {
        $count = Clinic::whereNotNull('wa_plan')
            ->where('wa_messages_quota', '>', 0)
            ->update([
                'wa_messages_used'     => 0,
                'wa_messages_reset_at' => now(),
            ]);

        $this->info("WhatsApp quotas reset for {$count} clinic(s).");

        return self::SUCCESS;
    }
}
