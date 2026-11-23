<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    public static function log(string $action, ?string $type = null, ?int $id = null, array $changes = []): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'changes' => $changes ?: null,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
