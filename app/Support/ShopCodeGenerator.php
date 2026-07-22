<?php

namespace App\Support;

use App\Models\ShopCode;
use Illuminate\Support\Str;

final class ShopCodeGenerator
{
    public static function slugify(string $name): string
    {
        $base = strtoupper(Str::ascii($name));
        $base = preg_replace('/[^A-Z0-9]/', '', $base) ?? '';
        $base = substr($base, 0, 10);

        return $base !== '' ? $base : 'BOUTIK';
    }

    public static function generateUnique(string $name): string
    {
        $base = self::slugify($name);
        $code = $base;
        $suffix = 1;

        while (ShopCode::where('code', $code)->exists()) {
            $suffix++;
            $code = $base.$suffix;
        }

        return $code;
    }
}
