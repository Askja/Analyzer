<?php


namespace models;

class StringsHelper
{
    static public function strReplaceAssoc(array $replace, $subject): array|string
    {
        return str_replace(array_keys($replace), array_values($replace), $subject);
    }

    static public function convertString(string $line): array|bool|string|null
    {
        return mb_convert_encoding($line, 'cp1251', 'utf-8');
    }
}