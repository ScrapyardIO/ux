<?php

namespace ScrapyardIO\UX\Enums;

use ScrapyardIO\UX\Support\Color;

/**
 * Named clear-colour presets for the {@see \ScrapyardIO\UX\Runner\Sketches\UXGuiColorMenu\UxGuiColorMenu} sketch.
 */
enum GuiBackdrop: string
{
    case MIDNIGHT = 'Midnight';
    case STEEL = 'Steel';
    case FOREST = 'Forest';
    case EMBER = 'Ember';
    case VIOLET = 'Violet';

    public function hex(): string
    {
        return match ($this) {
            self::MIDNIGHT => '#141820',
            self::STEEL => '#1B2433',
            self::FOREST => '#14241A',
            self::EMBER => '#2A1814',
            self::VIOLET => '#1C1428',
        };
    }

    public function color(): Color
    {
        return Color::fromHex($this->hex());
    }

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }

    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $label) {
                return $case;
            }
        }

        return null;
    }
}
