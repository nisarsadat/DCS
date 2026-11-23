<?php

namespace App\Console\Commands;

use App\Database\LedgerCheck;
use App\Database\SchemaDialect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostgresMigrationTest extends Command
{
    protected $signature = 'clinic:pg-migrate-test';

    protected $description = 'Migrate a Neon development branch and roll back a ledger smoke test';

    public function handle(): int
    {
        $branch = (string) env('NEON_BRANCH', '');
        $url = (string) env('NEON_DEV_DATABASE_URL', '');
        if (! $this->allowed($branch, $url)) {
            return self::FAILURE;
        }

        $url = SchemaDialect::normalizePostgresUrl($url);
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            $this->error('NEON_DEV_DATABASE_URL could not be read.');

            return self::FAILURE;
        }
        config([
            'database.connections.pgsql_dev' => [
                'driver' => 'pgsql',
                'host' => $parts['host'],
                'port' => (string) ($parts['port'] ?? '5432'),
                'database' => ltrim((string) ($parts['path'] ?? '/postgres'), '/'),
                'username' => rawurldecode((string) ($parts['user'] ?? '')),
                'password' => rawurldecode((string) ($parts['pass'] ?? '')),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'require',
            ],
        ]);

        DB::purge('pgsql_dev');
        $original = config('database.default');
        DB::setDefaultConnection('pgsql_dev');

        try {
            $connected = false;
            $last = null;
            for ($attempt = 1; $attempt <= 4; $attempt++) {
                try {
                    DB::purge('pgsql_dev');
                    DB::connection('pgsql_dev')->select('select 1 as ok');
                    $connected = true;
                    break;
                } catch (Throwable $exception) {
                    $last = $exception;
                    sleep(3);
                }
            }
            if (! $connected) {
                throw $last ?? new \RuntimeException('Could not connect to the development branch.');
            }
            if (DB::getDriverName() !== 'pgsql') {
                $this->error('Refusing to migrate because the development connection is not PostgreSQL.');

                return self::FAILURE;
            }
            $exit = Artisan::call('migrate', [
                '--database' => 'pgsql_dev',
                '--force' => true,
            ]);
            $output = $this->redact(Artisan::output());
            if ($exit !== 0) {
                $this->error(trim($output) !== '' ? trim($output) : 'Migration failed.');

                return self::FAILURE;
            }

            LedgerCheck::assertBalances();
            $tables = DB::select("select count(*) as total from information_schema.tables where table_schema = 'public' and table_type = 'BASE TABLE'");
            $count = (int) ($tables[0]->total ?? 0);
            $this->info('Development branch migrations finished. Public tables: '.$count.'. Ledger balances matched and the smoke rows were rolled back.');
            if (trim($output) !== '') {
                $this->line($output);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($this->redact($exception->getMessage()));
            $this->error(basename($exception->getFile()).':'.$exception->getLine());

            return self::FAILURE;
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('pgsql_dev');
        }
    }

    private function allowed(string $branch, string $url): bool
    {
        if ($branch === '' || $url === '') {
            $this->error('Set NEON_BRANCH and NEON_DEV_DATABASE_URL. This command does not use the local MySQL database or the Neon production branch.');

            return false;
        }

        if (preg_match('/production|\\bprod\\b/i', $branch) || ! preg_match('/dev|test|preview|staging/i', $branch)) {
            $this->error('NEON_BRANCH must be a development, test, preview, or staging branch.');

            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! in_array($scheme, ['pgsql', 'postgresql', 'postgres'], true)) {
            $this->error('NEON_DEV_DATABASE_URL must be a PostgreSQL connection URL.');

            return false;
        }

        return true;
    }

    private function redact(string $message): string
    {
        return preg_replace('#postgres(?:ql)?://\\S+#i', '[database-url]', $message) ?? $message;
    }
}
