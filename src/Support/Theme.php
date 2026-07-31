<?php

namespace ScrapyardIO\UX\Support;

use Fabricate\UX\Color;
use Throwable;

/**
 * The palette and default metrics a node reaches for when the caller did not say.
 *
 * Read through here rather than through `config()` directly for two reasons. A
 * node has to be constructible with no container at all — a package test builds a
 * tree without ever booting a Machine — and the lookup happens in constructors,
 * so it has to be cheap enough to do repeatedly.
 *
 * Values are resolved once and cached. {@see flush()} exists for the service
 * provider, which merges the package config after the first node may already have
 * read a default, and for tests that override the palette.
 */
final class Theme
{
    /**
     * @var array<string, mixed>|null
     */
    protected static ?array $values = null;

    /**
     * @var array<string, Color>
     */
    protected static array $colors = [];

    public static function color(string $key): Color
    {
        if (isset(self::$colors[$key])) {
            return self::$colors[$key];
        }

        $palette = self::section('palette');
        $value = $palette[$key] ?? null;

        return self::$colors[$key] = is_string($value)
            ? self::parse($value)
            : Color::white();
    }

    public static function metric(string $key, int $fallback = 0): int
    {
        $value = self::section('metrics')[$key] ?? null;

        return is_int($value) ? $value : $fallback;
    }

    /**
     * The font every text node starts with, as a registered name. Null is the
     * built-in classic 5x7.
     */
    public static function font(): ?string
    {
        $value = self::section('text')['font'] ?? null;

        return is_string($value) ? $value : null;
    }

    public static function textSize(): int
    {
        $value = self::section('text')['size'] ?? null;

        return is_int($value) ? max(1, $value) : 1;
    }

    /**
     * Replace the resolved values wholesale, for a test that needs a known
     * palette rather than whatever the application configured.
     *
     * @param  array<string, mixed>  $values
     */
    public static function override(array $values): void
    {
        self::$values = array_replace_recursive(self::defaults(), $values);
        self::$colors = [];
    }

    public static function flush(): void
    {
        self::$values = null;
        self::$colors = [];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function section(string $key): array
    {
        $section = self::values()[$key] ?? [];

        return is_array($section) ? $section : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function values(): array
    {
        if (! is_null(self::$values)) {
            return self::$values;
        }

        return self::$values = array_replace_recursive(self::defaults(), self::configured());
    }

    /**
     * Whatever the application published, or nothing at all when there is no
     * container — a node library must not require a booted Machine to describe a
     * colour.
     *
     * @return array<string, mixed>
     */
    protected static function configured(): array
    {
        if (! function_exists('config')) {
            return [];
        }

        try {
            $configured = config('ux', []);
        } catch (Throwable) {
            return [];
        }

        return is_array($configured) ? $configured : [];
    }

    /**
     * Mirrors config/ux.php, so an application that never publishes the config
     * still gets the same palette the package documents.
     *
     * @return array<string, mixed>
     */
    protected static function defaults(): array
    {
        return [
            'palette' => [
                'surface' => '#000000',
                'panel' => '#201D2B',
                'ink' => '#FFFFFF',
                'muted' => '#897F99',
                'accent' => '#63E6C2',
                'outline' => '#3A344A',
                'track' => '#09080E',
                'warning' => '#EC5D9D',
            ],
            'metrics' => [
                'bar_thickness' => 6,
                'thumb_radius' => 4,
                'gap' => 2,
                'radius' => 0,
                'stroke' => 1,
                'pixel_radius' => 3,
                'gauge_ticks' => 5,
                'sparkline_samples' => 32,
            ],
            'text' => [
                'font' => null,
                'size' => 1,
            ],
        ];
    }

    /**
     * A malformed hex is a configuration mistake, not a reason to refuse to
     * paint, so it degrades to ink rather than throwing mid-frame.
     */
    protected static function parse(string $hex): Color
    {
        try {
            return Color::fromHex($hex);
        } catch (Throwable) {
            return Color::white();
        }
    }
}
