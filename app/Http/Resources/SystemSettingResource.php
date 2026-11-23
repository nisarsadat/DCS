<?php

namespace App\Http\Resources;

use App\Models\SystemSetting;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Event\Telemetry\System;

class SystemSettingResource extends JsonResource
{ 
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "name"=> $this->name,
            "email"=> $this->email,
            "address"=> $this->address,
            "phone"=> $this->phone,
            "date_type" => $this->date_type ?: SystemSetting::DATE_SHAMSI,
            "default_language" => in_array($this->default_language, ['en', 'fa', 'pa'], true)
                ? $this->default_language
                : SystemSetting::LANGUAGE_EN,
            "default_theme" => $this->default_theme === SystemSetting::THEME_DARK
                ? SystemSetting::THEME_DARK
                : SystemSetting::THEME_LIGHT,
            "photo" => Media::url($this[SystemSetting::COLUMN_IMAGE] ?? null),
            // This refers to the path of the photo stored in the database.
            //  If it exists, the asset() function will generate a URL for it.


        ];
        }
}
