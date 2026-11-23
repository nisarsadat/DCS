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
            $table->string(SystemSetting::COLUMN_DEFAULT_LANGUAGE, 10)
                ->default(SystemSetting::LANGUAGE_EN)
                ->after(SystemSetting::COLUMN_DATE_TYPE);
            $table->string(SystemSetting::COLUMN_DEFAULT_THEME, 20)
                ->default(SystemSetting::THEME_LIGHT)
                ->after(SystemSetting::COLUMN_DEFAULT_LANGUAGE);
        });
    }

    public function down(): void
    {
        Schema::table((new SystemSetting())->getTable(), function (Blueprint $table) {
            $table->dropColumn([
                SystemSetting::COLUMN_DEFAULT_LANGUAGE,
                SystemSetting::COLUMN_DEFAULT_THEME,
            ]);
        });
    }
};
