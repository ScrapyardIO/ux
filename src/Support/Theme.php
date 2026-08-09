<?php

namespace ScrapyardIO\UX\Support;

use Throwable;

/**
 * Palette and default metrics when the caller did not specify.
 *
 * Constructible without a booted Machine — package tests build trees offline.
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
                'status_bar_height' => 16,
            ],
            'text' => [
                'font' => null,
                'size' => 1,
            ],
        ];
    }

    protected static function parse(string $hex): Color
    {
        try {
            return Color::fromHex($hex);
        } catch (Throwable) {
            return Color::white();
        }
    }
}
