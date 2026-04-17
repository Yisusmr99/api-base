<?php

namespace App\Http\Helpers;

use Carbon\Carbon;

class DateHelper
{
    private const TIMEZONE = 'America/Guatemala';

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }
}
