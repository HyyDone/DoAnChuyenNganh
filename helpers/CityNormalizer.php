<?php

class CityNormalizer
{
    public static function normalize(?string $city): ?string
    {
        if (!$city) return null;

        $city = trim($city);

        $cityMap = [
            'hà nội' => 'Ha Noi',
            'ho chi minh city' => 'Ho Chi Minh',
            'hcmc' => 'Ho Chi Minh',
            'ha noi' => 'Ha Noi',
            'hanoi' => 'Ha Noi',
            'hn' => 'Ha Noi',
            'ho chi minh' => 'Ho Chi Minh',
            'tp hcm' => 'Ho Chi Minh',
            'tp.hcm' => 'Ho Chi Minh',
            'tphcm' => 'Ho Chi Minh',
            'hcm' => 'Ho Chi Minh',
            'đà nẵng' => 'Da Nang',
            'da nang' => 'Da Nang',
            'dn' => 'Da Nang'
        ];

        $key = function_exists('mb_strtolower') ? mb_strtolower($city) : strtolower($city);
        return $cityMap[$key] ?? $city;
    }
}
