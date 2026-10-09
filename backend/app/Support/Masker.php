<?php

namespace App\Support;

class Masker
{
    /** "Ahmad Fauzan" → "Ahmad F***" agar privasi terjaga pada halaman publik. */
    public static function nama(string $nama): string
    {
        $parts = preg_split('/\s+/', trim($nama));
        $first = array_shift($parts);

        return trim($first.' '.implode(' ', array_map(fn ($p) => mb_substr($p, 0, 1).'***', $parts)));
    }
}
