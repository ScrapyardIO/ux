<?php

namespace ScrapyardIO\UX\Support;

use Fabricate\NutsAndBolts\MagicAliases\Font;
use Fabricate\Rendering\Fonts\ClassicFont;
use Fabricate\Rendering\Fonts\GFXFont;
use Throwable;

/**
 * Font lookup that cannot break a frame.
 *
 * Two hazards make this worth centralising. The registry is a container binding,
 * so resolving a name throws outright when there is no Machine — which is exactly
 * the situation a package test is in. And several registered fonts are scaffolding
 * stubs with no bitmap payload at all: selecting one paints nothing and looks like
 * a rendering bug rather than a missing asset.
 *
 * Both cases resolve to the built-in classic 5x7 instead, represented as null
 * because that is the renderer's own fast path for it.
 */
final class Fonts
{
    /**
     * The GFXFont a name refers to, or null for the classic font.
     *
     * Resolved to an object rather than left as a name so that painting never
     * touches the container: the renderer would otherwise look the name up again
     * on every frame.
     */
    public static function resolve(?string $name): ?GFXFont
    {
        if (is_null($name) || ($name === '') || ($name === 'classic')) {
            return null;
        }

        try {
            $font = Font::font($name);
        } catch (Throwable) {
            return null;
        }

        if ($font instanceof ClassicFont) {
            return null;
        }

        return $font->hasBitmapData() ? $font : null;
    }

    /**
     * Whether a name resolves to something that will actually put ink down.
     * Classic always will.
     */
    public static function isDrawable(string $name): bool
    {
        return ($name === 'classic') || ! is_null(self::resolve($name));
    }

    /**
     * Every registered font that has glyph data, in registry order, with classic
     * first because it is always available.
     *
     * @return array<int, string>
     */
    public static function drawableNames(): array
    {
        try {
            $registered = array_keys(Font::listFonts());
        } catch (Throwable) {
            return ['classic'];
        }

        $names = [];

        foreach ($registered as $name) {
            if (is_string($name) && self::isDrawable($name)) {
                $names[] = $name;
            }
        }

        return ($names === []) ? ['classic'] : $names;
    }
}
