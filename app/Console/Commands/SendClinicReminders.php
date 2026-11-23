<?php

namespace App\Console\Commands;

use App\Services\ReminderDispatchService;
use Illuminate\Console\Command;

class SendClinicReminders extends Command
{
    protected $signature = 'clinic:reminders';

    protected $description = 'Send due clinic reminders through configured channels';

    public function handle(ReminderDispatchService $reminders): int
    {
        $result = $reminders->sendDue();
        $this->info('Sent '.$result['sent'].', skipped '.$result['skipped'].'.');

        return self::SUCCESS;
    }
}
