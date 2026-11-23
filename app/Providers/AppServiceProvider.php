<?php

namespace App\Providers;

use App\Database\NeonPostgres;
use App\Database\SchemaDialect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::extend('pgsql', function (array $config, ?string $name = null) {
            $config['name'] = $name;
            $config['prefix'] = $config['prefix'] ?? '';

            return NeonPostgres::connect($config);
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $postgresUrl = config('database.connections.pgsql.url');
        if (is_string($postgresUrl) && str_contains($postgresUrl, 'channel_binding=')) {
            config([
                'database.connections.pgsql.url' => SchemaDialect::normalizePostgresUrl($postgresUrl),
            ]);
        }

    }
}
