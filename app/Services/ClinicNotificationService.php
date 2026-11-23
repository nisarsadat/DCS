<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\NotificationState;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClinicNotificationService
{
    public function forUser(User $user): array
    {
        $items = $this->collect();
        $disabled = [];
        if (Schema::hasTable('notification_preferences')) {
            $disabled = NotificationPreference::query()
                ->where('user_id', $user->id)
                ->where('in_app', false)
                ->pluck('type')
                ->all();
        }

        $read = collect();
        if (Schema::hasTable('notification_states')) {
            $read = NotificationState::query()
                ->where('user_id', $user->id)
                ->whereNotNull('read_at')
                ->pluck('read_at', 'source_key');
        }

        $items = array_values(array_filter($items, fn ($item) => ! in_array($item['type'], $disabled, true)));

        foreach ($items as &$item) {
            $item['read'] = $read->has($item['id']);
            $item['read_at'] = optional($read->get($item['id']))->toIso8601String();
        }

        usort($items, function ($a, $b) {
            $rank = ['high' => 0, 'medium' => 1, 'low' => 2];
            $byPriority = ($rank[$a['priority']] ?? 3) <=> ($rank[$b['priority']] ?? 3);
            if ($byPriority !== 0) {
                return $byPriority;
            }

            return strcmp($b['at'], $a['at']);
        });

        return $items;
    }

    public function markRead(User $user, array $keys): void
    {
        if (! Schema::hasTable('notification_states')) {
            return;
        }

        foreach ($keys as $key) {
            NotificationState::query()->updateOrCreate(
                ['user_id' => $user->id, 'source_key' => $key],
                ['read_at' => now()]
            );
        }
    }

    public function collect(): array
    {
        $items = [];
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        if (Schema::hasTable('appointments') && Schema::hasTable('people')) {
            $rows = DB::table('appointments')
                ->join('people', 'appointments.people_id', '=', 'people.id')
                ->select(array_filter([
                    'appointments.id',
                    'appointments.date_time',
                    'appointments.status',
                    'people.name',
                    'people.phone',
                    'people.email',
                    Schema::hasColumn('people', 'notify_consent') ? 'people.notify_consent' : null,
                ]))
                ->where('appointments.date_time', '>=', $now->copy()->subDays(14))
                ->where('appointments.date_time', '<=', $now->copy()->addDays(2))
                ->orderBy('appointments.date_time')
                ->limit(80)
                ->get();

            foreach ($rows as $row) {
                $when = Carbon::parse($row->date_time);
                $status = strtolower((string) $row->status);
                $closed = in_array($status, ['completed', 'cancelled', 'canceled', 'noshow', 'no_show', 'no-show'], true);

                $contact = $this->contact($row);
                if (! $closed && $when->isFuture() && $when->lte($now->copy()->addDay())) {
                    $items[] = $this->item(
                        'appointment:'.$row->id.':upcoming',
                        'appointment',
                        'high',
                        'Upcoming appointment',
                        trim($row->name.' · '.$when->format('Y-m-d H:i')),
                        '/appointments',
                        $when->toIso8601String(),
                        $contact
                    );
                } elseif (! $closed && $when->isPast()) {
                    $items[] = $this->item(
                        'appointment:'.$row->id.':missed',
                        'missed',
                        'high',
                        'Missed appointment',
                        trim($row->name.' · '.$when->format('Y-m-d H:i')),
                        '/appointments',
                        $when->toIso8601String(),
                        $contact
                    );
                }
            }
        }

        if (Schema::hasTable('cures')) {
            $cures = DB::table('cures')
                ->leftJoin('people', 'cures.patient_id', '=', 'people.id')
                ->whereNull('cures.deleted_at')
                ->where(function ($query) {
                    $query->whereRaw('LOWER(cures.status) not in (?, ?, ?)', ['completed', 'cancelled', 'canceled'])
                        ->orWhereColumn('cures.paid', '<', 'cures.grand_total');
                })
                ->orderByDesc('cures.id')
                ->limit(12)
                ->get(['cures.id', 'cures.status', 'cures.grand_total', 'cures.paid', 'people.name', 'cures.updated_at']);

            foreach ($cures as $cure) {
                if (strtolower((string) $cure->status) === 'completed' && (float) $cure->paid >= (float) $cure->grand_total) {
                    continue;
                }
                $items[] = $this->item(
                    'cure:'.$cure->id.':open',
                    'treatment',
                    'medium',
                    'Incomplete treatment',
                    trim(($cure->name ?: 'Patient').' · '.$cure->status),
                    '/cure',
                    Carbon::parse($cure->updated_at ?? now())->toIso8601String()
                );
            }
        }

        foreach (['outbound_labs' => '/laboratory', 'inbound_labs' => '/mainLaboratory'] as $table => $link) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'return_date')) {
                continue;
            }
            $labs = DB::table($table)
                ->whereNull('deleted_at')
                ->whereNotNull('return_date')
                ->whereBetween('return_date', [$today->copy()->subDays(30)->toDateString(), $today->copy()->addDays(3)->toDateString()])
                ->orderBy('return_date')
                ->limit(40)
                ->get(['id', 'return_date', 'description']);

            foreach ($labs as $lab) {
                $due = Carbon::parse($lab->return_date)->startOfDay();
                $label = $lab->description ?: ('#'.$lab->id);
                if ($due->lt($today)) {
                    $items[] = $this->item('lab:'.$table.':'.$lab->id.':overdue', 'lab', 'high', 'Overdue laboratory order', $label.' · '.$due->toDateString(), $link, $due->toIso8601String());
                } elseif ($due->equalTo($today)) {
                    $items[] = $this->item('lab:'.$table.':'.$lab->id.':ready', 'lab', 'medium', 'Laboratory order due today', $label, $link, $due->toIso8601String());
                } else {
                    $items[] = $this->item('lab:'.$table.':'.$lab->id.':soon', 'lab', 'low', 'Upcoming laboratory delivery', $label.' · '.$due->toDateString(), $link, $due->toIso8601String());
                }
            }
        }

        if (Schema::hasTable('people_accounts') && Schema::hasTable('people')) {
            $balances = DB::table('people_accounts')
                ->join('people', 'people_accounts.people_id', '=', 'people.id')
                ->where('people.type', 'patient')
                ->where('people_accounts.balance', '>', 0)
                ->whereNull('people_accounts.deleted_at')
                ->orderByDesc('people_accounts.balance')
                ->limit(15)
                ->get(array_filter([
                    'people.id',
                    'people.name',
                    'people.phone',
                    'people.email',
                    Schema::hasColumn('people', 'notify_consent') ? 'people.notify_consent' : null,
                    'people_accounts.balance',
                    'people_accounts.updated_at',
                ]));

            foreach ($balances as $balance) {
                $items[] = $this->item(
                    'balance:'.$balance->id,
                    'payment',
                    'high',
                    'Outstanding payment',
                    $balance->name.' · '.number_format((float) $balance->balance, 2).' AFN',
                    '/patients',
                    Carbon::parse($balance->updated_at ?? now())->toIso8601String(),
                    $this->contact($balance)
                );
            }
        }

        if (Schema::hasTable('inventory_items')) {
            $stock = DB::table('inventory_items')->where('is_active', true)->get();
            foreach ($stock as $item) {
                if ((float) $item->quantity <= 0) {
                    $items[] = $this->item('stock:'.$item->id.':out', 'inventory', 'high', 'Out of stock', $item->name, '/inventory', now()->toIso8601String());
                } elseif ((float) $item->quantity <= (float) $item->min_stock) {
                    $items[] = $this->item('stock:'.$item->id.':low', 'inventory', 'medium', 'Low inventory', $item->name.' · '.$item->quantity.' '.$item->unit, '/inventory', now()->toIso8601String());
                }
                if ($item->expires_at) {
                    $expiry = Carbon::parse($item->expires_at)->startOfDay();
                    if ($expiry->lt($today)) {
                        $items[] = $this->item('stock:'.$item->id.':expired', 'inventory', 'high', 'Expired material', $item->name.' · '.$expiry->toDateString(), '/inventory', $expiry->toIso8601String());
                    } elseif ($expiry->lte($today->copy()->addDays(30))) {
                        $items[] = $this->item('stock:'.$item->id.':expiring', 'inventory', 'medium', 'Expiring material', $item->name.' · '.$expiry->toDateString(), '/inventory', $expiry->toIso8601String());
                    }
                }
            }
        }

        $backup = $this->backupStatus();
        $items[] = $this->item(
            'backup:'.($backup['stamp'] ?: 'none'),
            'backup',
            $backup['ok'] ? 'low' : 'high',
            $backup['ok'] ? 'Database backup is current' : 'Database backup needs attention',
            $backup['message'],
            '/systemSetting',
            $backup['stamp'] ?: now()->toIso8601String()
        );

        return $items;
    }

    public function backupStatus(): array
    {
        $dir = storage_path('app/backups');
        $failure = $dir.DIRECTORY_SEPARATOR.'last-failure.txt';
        if (is_file($failure) && filemtime($failure) > (time() - 86400 * 2)) {
            return [
                'ok' => false,
                'message' => trim((string) file_get_contents($failure)) ?: 'The last backup failed.',
                'stamp' => date('c', filemtime($failure)),
            ];
        }

        $latest = null;
        if (is_dir($dir)) {
            foreach (glob($dir.DIRECTORY_SEPARATOR.'*.sql') ?: [] as $file) {
                $mtime = filemtime($file);
                if ($latest === null || $mtime > $latest) {
                    $latest = $mtime;
                }
            }
        }

        if ($latest === null) {
            return ['ok' => false, 'message' => 'No database backup has been saved yet.', 'stamp' => ''];
        }

        $ageDays = (time() - $latest) / 86400;
        if ($ageDays > 2) {
            return ['ok' => false, 'message' => 'The newest backup is older than 2 days.', 'stamp' => date('c', $latest)];
        }

        return ['ok' => true, 'message' => 'Last backup '.Carbon::createFromTimestamp($latest)->diffForHumans().'.', 'stamp' => date('c', $latest)];
    }

    private function item(string $id, string $type, string $priority, string $title, string $body, string $link, string $at, array $extra = []): array
    {
        return array_merge(compact('id', 'type', 'priority', 'title', 'body', 'link', 'at'), $extra);
    }

    private function contact(object $row): array
    {
        return [
            'phone' => $row->phone ?? null,
            'email' => $row->email ?? null,
            'consent' => (bool) ($row->notify_consent ?? false),
        ];
    }
}
