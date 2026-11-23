<?php

namespace App\Models;

use Hamcrest\SelfDescribing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;
    public const COLUMN_NAME='name';
    public const COLUMN_EMAIL='email';
    public const COLUMN_PHONE='phone';
    public const COLUMN_IMAGE='image';
    public  const COLUMN_ADDRESS='address';
    public const COLUMN_DATE_TYPE = 'date_type';
    public const COLUMN_DEFAULT_LANGUAGE = 'default_language';
    public const COLUMN_DEFAULT_THEME = 'default_theme';
    public const DATE_SHAMSI = 'jalali';
    public const DATE_MILADI = 'gregorian';
    public const LANGUAGE_EN = 'en';
    public const LANGUAGE_FA = 'fa';
    public const LANGUAGE_PA = 'pa';
    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';

    protected $table='system_settings';


    protected $fillable = [
        self::COLUMN_NAME,
        self::COLUMN_EMAIL,
        self::COLUMN_PHONE,
        self::COLUMN_IMAGE,
        self::COLUMN_ADDRESS,
        self::COLUMN_DATE_TYPE,
        self::COLUMN_DEFAULT_LANGUAGE,
        self::COLUMN_DEFAULT_THEME,
    ];

    public $images = [ 
        self::COLUMN_IMAGE
    ];

    public static function currentDateType(): string
    {
        $request = function_exists('request') ? request() : null;

        if ($request && $request->attributes->has('system_date_type')) {
            return $request->attributes->get('system_date_type');
        }

        $stored = static::query()->value(self::COLUMN_DATE_TYPE);
        $type = $stored === self::DATE_MILADI ? self::DATE_MILADI : self::DATE_SHAMSI;

        if ($request) {
            $request->attributes->set('system_date_type', $type);
        }

        return $type;
    }
}
