<?php

namespace App\Services;

use App\Models\NotificationDispatch;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class ReminderDispatchService
{
    public function __construct(private ClinicNotificationService $notifications)
    {
    }

    public function sendDue(): array
    {
        if (! Schema::hasTable('notification_dispatches') || ! Schema::hasTable('notification_preferences')) {
            return ['sent' => 0, 'skipped' => 0];
        }

        $sent = 0;
        $skipped = 0;
        $today = now()->toDateString();
        $items = array_filter($this->notifications->collect(), fn ($item) => $item['priority'] === 'high');

        $preferences = NotificationPreference::query()
            ->where(function ($query) {
                $query->where('sms', true)->orWhere('email', true)->orWhere('whatsapp', true);
            })
            ->get();

        foreach ($preferences as $preference) {
            foreach ($items as $item) {
                if ($item['type'] !== $preference->type) {
                    continue;
                }
                foreach (['sms', 'email', 'whatsapp'] as $channel) {
                    if (! $preference->{$channel}) {
                        continue;
                    }
                    $exists = NotificationDispatch::query()
                        ->where('source_key', $item['id'])
                        ->where('channel', $channel)
                        ->whereDate('sent_on', $today)
                        ->exists();
                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    $result = $this->deliver($channel, $item);
                    NotificationDispatch::query()->create([
                        'source_key' => $item['id'],
                        'channel' => $channel,
                        'status' => $result['status'],
                        'detail' => $result['detail'],
                        'sent_on' => $today,
                        'created_at' => now(),
                    ]);
                    $result['status'] === 'sent' ? $sent++ : $skipped++;
                }
            }
        }

        return compact('sent', 'skipped');
    }

    private function deliver(string $channel, array $item): array
    {
        $webhook = match ($channel) {
            'sms' => config('services.reminders.sms_webhook'),
            'whatsapp' => config('services.reminders.whatsapp_webhook'),
            default => null,
        };

        if (array_key_exists('consent', $item) && ! $item['consent']) {
            return ['status' => 'skipped', 'detail' => 'Patient has not consented to messages.'];
        }

        if ($channel === 'email') {
            $to = $item['email'] ?? config('services.reminders.email_to');
            if (! $to) {
                return ['status' => 'skipped', 'detail' => 'Email recipient is not configured.'];
            }
            try {
                Mail::raw($item['title']."\n".$item['body'], function ($message) use ($to, $item) {
                    $message->to($to)->subject($item['title']);
                });

                return ['status' => 'sent', 'detail' => 'email'];
            } catch (\Throwable $exception) {
                Log::warning('Reminder email failed', ['error' => $exception->getMessage()]);

                return ['status' => 'skipped', 'detail' => 'Mail is not configured.'];
            }
        }

        if (! $webhook) {
            return ['status' => 'skipped', 'detail' => 'Provider webhook is not configured.'];
        }

        try {
            Http::timeout(8)->post($webhook, [
                'title' => $item['title'],
                'body' => $item['body'],
                'channel' => $channel,
            ]);

            return ['status' => 'sent', 'detail' => 'webhook'];
        } catch (\Throwable $exception) {
            Log::warning('Reminder webhook failed', ['channel' => $channel, 'error' => $exception->getMessage()]);

            return ['status' => 'skipped', 'detail' => 'Provider request failed.'];
        }
    }
}
