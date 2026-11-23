<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table((new SystemSetting())->getTable(), function (Blueprint $table) {
            $table->string(SystemSetting::COLUMN_DATE_TYPE, 20)
                ->default(SystemSetting::DATE_SHAMSI)
                ->after(SystemSetting::COLUMN_ADDRESS);
        });
    }

    public function down(): void
    {
        Schema::table((new SystemSetting())->getTable(), function (Blueprint $table) {
            $table->dropColumn(SystemSetting::COLUMN_DATE_TYPE);
        });
    }
};
