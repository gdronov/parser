<?php

namespace Gdronov\DromParser;

class FrameType
{
    private const TYPES = [
        1  => 'Купе',
        3  => 'Универсал',
        4  => 'Хэтчбек 3 дв.',
        5  => 'Хэтчбек 5 дв.',
        6  => 'Минивэн',
        7  => 'Джип 5 дв.',
        8  => 'Джип 3 дв.',
        9  => 'Лифтбек',
        10 => 'Седан',
        11 => 'Открытый',
        12 => 'Пикап',
    ];

    public static function name(?int $id): ?string
    {
        return array_key_exists($id, self::TYPES)
            ? self::TYPES[$id]
            : null;
    }
}
