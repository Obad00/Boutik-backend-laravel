<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Ids
{
    public static function generate(string $prefix): string
    {
        return $prefix.'_'.Str::lower(Str::random(7));
    }
}
