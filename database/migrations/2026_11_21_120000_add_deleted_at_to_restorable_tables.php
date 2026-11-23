<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'people',
            'appointments',
            'leads',
            'cure_cycles',
            'categories',
            'stages',
            'services',
            'service_groups',
            'products',
            'expense_categories',
            'salaries',
        ] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'people',
            'appointments',
            'leads',
            'cure_cycles',
            'categories',
            'stages',
            'services',
            'service_groups',
            'products',
            'expense_categories',
            'salaries',
        ] as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
