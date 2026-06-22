<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Generowanie slugów SEO-friendly (z transliteracją polskich znaków).
 */
final class Slugger
{
    private const MAP = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z',
        'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
        'Ó' => 'o', 'Ś' => 's', 'Ż' => 'z', 'Ź' => 'z',
    ];

    public static function slugify(string $text, int $maxLength = 80): string
    {
        $text = strtr($text, self::MAP);

        // Transliteracja pozostałych znaków (np. é -> e)
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        if ($text === '') {
            $text = 'historia';
        }

        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
            $text = rtrim($text, '-');
        }

        return $text;
    }

    /**
     * Generuje unikalny slug korzystając z callbacka sprawdzającego istnienie.
     *
     * @param callable(string):bool $exists zwraca true jeśli slug już istnieje
     */
    public static function unique(string $text, callable $exists, int $maxLength = 80): string
    {
        $base = self::slugify($text, $maxLength);
        $slug = $base;
        $i = 2;

        while ($exists($slug)) {
            $suffix = '-' . $i;
            $slug = mb_substr($base, 0, $maxLength - mb_strlen($suffix)) . $suffix;
            $i++;
        }

        return $slug;
    }
}
