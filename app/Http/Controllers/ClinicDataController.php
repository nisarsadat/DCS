<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClinicDataController extends Controller
{
    private const KEEP = [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'personal_access_tokens',
        'migrations',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'system_settings',
    ];

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        return ['data' => $this->files()];
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);
        if (Artisan::call('clinic:backup') !== 0) {
            return response()->json([
                'message' => trim(Artisan::output()) ?: 'Backup failed.',
            ], 422);
        }

        return ['data' => $this->files(), 'message' => 'Backup saved.'];
    }

    public function download(Request $request, string $file)
    {
        $this->ensureAdmin($request);
        $path = $this->safePath($file);
        abort_unless(is_file($path), 404);

        return response()->download($path, basename($path));
    }

    public function upload(Request $request)
    {
        $this->ensureAdmin($request);
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $upload = $request->file('file');
        if (strtolower($upload->getClientOriginalExtension()) !== 'sql') {
            return response()->json(['message' => 'Upload a .sql backup file.'], 422);
        }

        $head = file_get_contents($upload->getRealPath(), false, null, 0, 200);
        if ($head === false || str_contains($head, '<?php')) {
            return response()->json(['message' => 'This file is not a database backup.'], 422);
        }

        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = 'clinic-upload-'.now()->format('Ymd-His').'.sql';
        $upload->move($directory, $name);
        $saved = $directory.DIRECTORY_SEPARATOR.$name;

        if (Artisan::call('clinic:restore', ['file' => $saved, '--force' => true]) !== 0) {
            return response()->json([
                'message' => trim(Artisan::output()) ?: 'Restore failed.',
            ], 422);
        }

        return ['message' => 'Data restored.', 'data' => $this->files()];
    }

    public function reset(Request $request)
    {
        $this->ensureAdmin($request);
        if ($request->input('confirm') !== 'RESET') {
            return response()->json(['message' => 'Confirmation is required.'], 422);
        }

        if (Artisan::call('clinic:backup') !== 0) {
            return response()->json([
                'message' => 'Backup failed, so nothing was reset.',
            ], 422);
        }

        $rows = $this->baseTables();

        if (DB::getDriverName() === 'pgsql') {
            $pending = $rows;
            for ($pass = 0; $pass < 8 && $pending !== []; $pass++) {
                $failed = [];
                foreach ($pending as $name) {
                    try {
                        DB::table($name)->delete();
                    } catch (\Throwable $exception) {
                        $failed[] = $name;
                    }
                }
                if ($failed === $pending) {
                    return response()->json([
                        'message' => 'Reset stopped because related records could not be cleared.',
                    ], 422);
                }
                $pending = $failed;
            }
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ($rows as $name) {
                    DB::table($name)->truncate();
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        if (Schema::hasTable('money_accounts') && DB::table('money_accounts')->count() === 0) {
            DB::table('money_accounts')->insert([
                'name' => 'Main Account',
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['message' => 'Clinic data was reset after a backup.', 'data' => $this->files()];
    }

    private function baseTables(): array
    {
        if (DB::getDriverName() === 'pgsql') {
            $rows = DB::select(
                "select tablename as name from pg_tables where schemaname = 'public'"
            );
        } else {
            $database = config('database.connections.mysql.database');
            $rows = DB::select(
                'select table_name as name from information_schema.tables where table_schema = ? and table_type = ?',
                [$database, 'BASE TABLE']
            );
        }

        $names = [];
        foreach ($rows as $row) {
            $values = array_change_key_case((array) $row, CASE_LOWER);
            $name = $values['name'] ?? null;
            if ($name && ! in_array($name, self::KEEP, true) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Admin'), 403, 'Only an admin can do this.');
    }

    private function files(): array
    {
        $directory = storage_path('app/backups');
        $paths = is_dir($directory) ? (glob($directory.DIRECTORY_SEPARATOR.'clinic-*.sql') ?: []) : [];
        usort($paths, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map(fn ($path) => [
            'name' => basename($path),
            'size' => $this->size((int) filesize($path)),
            'createdAt' => date('Y-m-d H:i', filemtime($path)),
        ], $paths);
    }

    private function safePath(string $file): string
    {
        $name = basename($file);
        abort_unless((bool) preg_match('/^clinic(?:-upload)?-\d{8}-\d{6}\.sql$/', $name), 404);

        return storage_path('app/backups/'.$name);
    }

    private function size(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
