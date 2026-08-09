<?php

namespace ScrapyardIO\UX\Support;

/**
 * Font name helper. Null (or classic) means the renderer classic font.
 */
final class Fonts
{
    /**
     * Resolve a font name for PaintContext / Renderer2D setFont.
     *
     * Returns null for the classic built-in path.
     */
    public static function resolve(?string $name): ?string
    {
        if (is_null($name) || $name === '' || $name === 'classic') {
            return null;
        }

        return $name;
    }

    public static function isClassic(?string $name): bool
    {
        return is_null(self::resolve($name));
    }
}
