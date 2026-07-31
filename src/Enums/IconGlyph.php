<?php

namespace ScrapyardIO\UX\Enums;

/**
 * The shapes {@see \ScrapyardIO\UX\Chrome\Icon} can draw from primitives.
 *
 * Vector rather than bitmap on purpose. A bitmap icon has one size, and the same
 * tree here has to read on a 128x64 panel and a 1024x768 window; a shape drawn
 * from the renderer's own primitives simply scales with the box it is given.
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

    /**
     * Whether the shape covers its whole box, which is the only case where an
     * icon may claim to be opaque.
     */
    public function isSolid(): bool
    {
        return $this === self::BOX;
    }
}
