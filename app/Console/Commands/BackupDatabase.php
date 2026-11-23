<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'clinic:backup';

    protected $description = 'Save a SQL backup of the clinic database';

    public function handle(): int
    {
        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $directory.DIRECTORY_SEPARATOR.'clinic-'.now()->format('Ymd-His').'.sql';
        $driver = DB::getDriverName();
        $connection = config('database.connections.'.$driver);
        $password = (string) ($connection['password'] ?? '');

        if ($driver === 'pgsql') {
            $command = [
                env('DUMP_PATH', 'pg_dump'),
                '--host='.($connection['host'] ?? '127.0.0.1'),
                '--port='.(string) ($connection['port'] ?? '5432'),
                '--username='.($connection['username'] ?? ''),
                '--format=plain',
                '--no-owner',
                '--file='.$file,
                $connection['database'] ?? '',
            ];
            $env = $this->processEnvironment(['PGPASSWORD' => $password]);
        } else {
            $defaults = $this->mysqlDefaultsFile($connection, $password);
            $command = [
                env('DUMP_PATH', 'mysqldump'),
                '--defaults-extra-file='.$defaults,
                '--single-transaction',
                '--routines',
                '--result-file='.$file,
                $connection['database'] ?? '',
            ];
            $env = $this->processEnvironment();
        }

        $process = new Process($command, null, $env);
        $process->setTimeout(300);
        $process->run();

        if (isset($defaults) && is_file($defaults)) {
            @unlink($defaults);
        }

        if (! $process->isSuccessful() || ! is_file($file) || filesize($file) === 0) {
            @unlink($file);
            file_put_contents(
                $directory.DIRECTORY_SEPARATOR.'last-failure.txt',
                'Backup failed at '.now()->toDateTimeString()
            );
            $this->error('Database backup failed.');

            return self::FAILURE;
        }

        @unlink($directory.DIRECTORY_SEPARATOR.'last-failure.txt');
        $this->prune($directory);
        $this->info('Backup saved.');

        return self::SUCCESS;
    }

    private function mysqlDefaultsFile(array $connection, string $password): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mycnf');
        $contents = "[client]\n"
            .'user="'.addslashes((string) ($connection['username'] ?? ''))."\"\n"
            .'password="'.addslashes($password)."\"\n"
            .'host="'.addslashes((string) ($connection['host'] ?? '127.0.0.1'))."\"\n"
            .'port='.(int) ($connection['port'] ?? 3306)."\n";
        file_put_contents($path, $contents);
        @chmod($path, 0600);

        return $path;
    }

    private function processEnvironment(array $extra = []): array
    {
        $env = [];
        foreach (array_merge($_SERVER, $_ENV, $extra) as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $env[$key] = (string) $value;
            }
        }

        return $env;
    }

    private function prune(string $directory): void
    {
        $files = glob($directory.DIRECTORY_SEPARATOR.'clinic-*.sql') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($files, 14) as $old) {
            @unlink($old);
        }
    }
}
