<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Services\ClinicNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function __construct(private ClinicNotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $items = $this->notifications->forUser($request->user());
        $unread = count(array_filter($items, fn ($item) => ! $item['read']));
        $filter = $request->query('filter');
        if ($filter === 'unread') {
            $items = array_values(array_filter($items, fn ($item) => ! $item['read']));
        } elseif ($filter === 'urgent') {
            $items = array_values(array_filter($items, fn ($item) => $item['priority'] === 'high'));
        } elseif ($filter && $filter !== 'all') {
            $items = array_values(array_filter($items, fn ($item) => $item['type'] === $filter));
        }

        if ($request->filled('limit')) {
            $items = array_slice($items, 0, $request->integer('limit'));
        }

        return [
            'unread' => $unread,
            'data' => $items,
        ];
    }

    public function read(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string', 'max:160'],
        ]);
        $this->notifications->markRead($request->user(), $data['ids']);

        return ['ok' => true];
    }

    public function readAll(Request $request)
    {
        $ids = array_column($this->notifications->forUser($request->user()), 'id');
        $this->notifications->markRead($request->user(), $ids);

        return ['ok' => true];
    }

    public function preferences(Request $request)
    {
        if (! Schema::hasTable('notification_preferences')) {
            return [];
        }

        return NotificationPreference::query()->where('user_id', $request->user()->id)->orderBy('type')->get();
    }

    public function savePreferences(Request $request)
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.type' => ['required', 'string', 'max:40'],
            'preferences.*.in_app' => ['required', 'boolean'],
            'preferences.*.sms' => ['required', 'boolean'],
            'preferences.*.email' => ['required', 'boolean'],
            'preferences.*.whatsapp' => ['required', 'boolean'],
        ]);

        if (! Schema::hasTable('notification_preferences')) {
            return response()->json(['message' => 'Notification settings are not ready yet.'], 503);
        }

        foreach ($data['preferences'] as $preference) {
            NotificationPreference::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'type' => $preference['type']],
                [
                    'in_app' => $preference['in_app'],
                    'sms' => $preference['sms'],
                    'email' => $preference['email'],
                    'whatsapp' => $preference['whatsapp'],
                ]
            );
        }

        return $this->preferences($request);
    }
}
