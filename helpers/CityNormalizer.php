<?php

class CityNormalizer
{
    public static function normalize(?string $city): ?string
    {
        if (!$city) return null;

        $city = trim($city);

        $cityMap = [
            'hà nội' => 'Ha Noi',
            'ha noi' => 'Ha Noi',
            'hồ chí minh' => 'Ho Chi Minh',
            'ho chi minh' => 'Ho Chi Minh',
            'tp hcm' => 'Ho Chi Minh',
            'đà nẵng' => 'Da Nang',
            'da nang' => 'Da Nang'
        ];

        $key = mb_strtolower($city);
        return $cityMap[$key] ?? $city;
    }
}
