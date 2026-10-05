<?php

namespace App\Enums;

class SongInstrument
{
    public const KEYBOARD = 'keyboard';
    public const GUITAR = 'guitar';
    public const ELECTRIC_GUITAR = 'electric_guitar';
    public const BASS = 'bass';
    public const DRUMS = 'drums';
    public const VOCALS = 'vocals';

    public static function options(): array
    {
        return [
            self::KEYBOARD => __('Keyboard'),
            self::GUITAR => __('Acoustic Guitar'),
            self::ELECTRIC_GUITAR => __('Electric Guitar'),
            self::BASS => __('Bass'),
            self::DRUMS => __('Drums'),
            self::VOCALS => __('Vocals'),
        ];
    }
}
