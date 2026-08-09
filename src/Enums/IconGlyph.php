<?php

namespace ScrapyardIO\UX\Enums;

/**
 * Vector shapes {@see \ScrapyardIO\UX\Components\Chrome\Icon} can draw from primitives.
 */
enum IconGlyph: string
{
    case CIRCLE = 'CIRCLE';
    case DISC = 'DISC';
    case SQUARE = 'SQUARE';
    case BOX = 'BOX';
    case TRIANGLE = 'TRIANGLE';
    case TRIANGLE_DOWN = 'TRIANGLE_DOWN';
    case DIAMOND = 'DIAMOND';
    case PLUS = 'PLUS';
    case CROSS = 'CROSS';
    case DOT = 'DOT';
    case CHEVRON_LEFT = 'CHEVRON_LEFT';
    case CHEVRON_RIGHT = 'CHEVRON_RIGHT';
    case CHEVRON_UP = 'CHEVRON_UP';
    case CHEVRON_DOWN = 'CHEVRON_DOWN';

    public function isSolid(): bool
    {
        return $this === self::BOX;
    }
}
