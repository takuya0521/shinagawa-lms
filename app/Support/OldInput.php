<?php

namespace App\Support;

final class OldInput
{
    public static function text(string $key, mixed $default = null): string
    {
        $value = old($key, $default);

        return is_scalar($value) ? (string) $value : '';
    }
}
