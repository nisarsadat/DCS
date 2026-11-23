<?php

namespace App\Console\Commands;

use App\Services\NeonStorage;
use Illuminate\Console\Command;
use Throwable;

class EnsureNeonBucket extends Command
{
    protected $signature = 'clinic:neon-bucket';

    protected $description = 'Create the private Neon assets bucket on a non-production branch when it is missing';

    public function handle(NeonStorage $storage): int
    {
        $branch = (string) env('NEON_BRANCH', '');
        if ($branch === '' || preg_match('/production|\\bprod\\b/i', $branch) || ! preg_match('/dev|test|preview|staging/i', $branch)) {
            $this->error('Refusing to change storage. Set NEON_BRANCH to a development, test, preview, or staging branch.');

            return self::FAILURE;
        }

        try {
            $storage->ensurePrivateBucket();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Private assets bucket is present on the selected branch.');

        return self::SUCCESS;
    }
}
