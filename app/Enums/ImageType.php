<?php

namespace App\Enums;

enum ImageType: string
{
    case Svga  = 'svga';
    case Alpha = 'alpha';
    case Vap   = 'vap';
    case Image = 'image';

    public function label(): string
    {
        return match($this) {
            self::Svga  => __('svga'),
            self::Alpha => __('alpha'),
            self::Vap   => __('vap'),
            self::Image => __('image:(jpeg,png,jpg,svg,etc)'),
        };
    }

    public static function options(): array
    {
        return [
            self::Svga->value  => self::Svga->label(),
            self::Alpha->value => self::Alpha->label(),
            self::Vap->value   => self::Vap->label(),
            self::Image->value => self::Image->label(),
        ];
    }
}
