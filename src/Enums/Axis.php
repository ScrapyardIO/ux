<?php

namespace ScrapyardIO\UX\Enums;

enum Axis: string
{
    case HORIZONTAL = 'HORIZONTAL';
    case VERTICAL = 'VERTICAL';

    public function extentOf(int $width, int $height): int
    {
        return $this === self::HORIZONTAL ? $width : $height;
    }
}
