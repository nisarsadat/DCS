<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class RestoreDatabase extends Command
{
    protected $signature = 'clinic:restore {file} {--force : Confirm that current data may be replaced}';

    protected $description = 'Restore the clinic database from a SQL backup after taking a fresh backup';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to restore without --force. A backup of the current database is taken first.');

            return self::FAILURE;
        }

        $file = $this->backupPath((string) $this->argument('file'));
        if ($file === null) {
            $this->error('Backup file was not found in storage/app/backups.');

            return self::FAILURE;
        }

        if (! $this->backupLooksSafe($file)) {
            $this->error('The file is not a database backup this server can restore.');

            return self::FAILURE;
        }

        if ($this->call('clinic:backup') !== self::SUCCESS) {
            $this->error('Backup failed, so the current data was left unchanged.');

            return self::FAILURE;
        }

        $driver = DB::getDriverName();
        $connection = config('database.connections.'.$driver);
        $password = (string) ($connection['password'] ?? '');

        if ($driver === 'pgsql') {
            $command = [
                env('PSQL_PATH', 'psql'),
                '--host='.($connection['host'] ?? '127.0.0.1'),
                '--port='.(string) ($connection['port'] ?? '5432'),
                '--username='.($connection['username'] ?? ''),
                '--dbname='.($connection['database'] ?? ''),
                '--file='.$file,
                '--single-transaction',
            ];
            $env = $this->processEnvironment(['PGPASSWORD' => $password]);
        } else {
            $defaults = $this->mysqlDefaultsFile($connection, $password);
            $mysql = dirname((string) env('DUMP_PATH', 'mysqldump')).DIRECTORY_SEPARATOR.'mysql';
            if (! is_file($mysql)) {
                $mysql = 'mysql';
            }
            $command = [
                $mysql,
                '--defaults-extra-file='.$defaults,
                $connection['database'] ?? '',
            ];
            $env = $this->processEnvironment();
        }

        $process = new Process($command, null, $env);
        $process->setTimeout(600);
        if ($driver !== 'pgsql') {
            $process->setInput(file_get_contents($file));
        }
        $process->run();

        if (isset($defaults) && is_file($defaults)) {
            @unlink($defaults);
        }

        if (! $process->isSuccessful()) {
            $this->error('Restore failed. The pre-restore backup is still in storage/app/backups.');

            return self::FAILURE;
        }

        $this->info('Database restored.');

        return self::SUCCESS;
    }

    private function backupPath(string $file): ?string
    {
        $directory = realpath(storage_path('app/backups'));
        if ($directory === false) {
            return null;
        }

        $candidate = realpath($directory.DIRECTORY_SEPARATOR.basename($file));
        if ($candidate === false || ! is_file($candidate)) {
            return null;
        }

        if (! str_starts_with($candidate, $directory)) {
            return null;
        }

        return $candidate;
    }

    private function backupLooksSafe(string $file): bool
    {
        $head = file_get_contents($file, false, null, 0, 4096);
        if ($head === false || str_contains($head, '<?php')) {
            return false;
        }

        $driver = DB::getDriverName();
        $marker = $driver === 'pgsql'
            ? (stripos($head, 'PostgreSQL') !== false || stripos($head, 'pg_dump') !== false)
            : (stripos($head, 'MySQL') !== false || stripos($head, 'MariaDB') !== false);

        if (! $marker) {
            return false;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return false;
        }

        foreach (['INTO OUTFILE', 'INTO DUMPFILE', 'LOAD_FILE(', 'pg_read_file', 'COPY PROGRAM', 'lo_import', 'lo_export'] as $forbidden) {
            if (stripos($contents, $forbidden) !== false) {
                return false;
            }
        }

        return true;
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
}
