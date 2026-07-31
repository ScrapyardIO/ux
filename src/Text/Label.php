<?php

namespace ScrapyardIO\UX\Text;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Concerns\DrawsText;
use ScrapyardIO\UX\UXNode;

/**
 * A run of text that sizes itself to its own ink.
 *
 * Intrinsic sizing is the point. A label asked to measure reports the extent of
 * the glyphs it will actually draw, so the containers around it can centre it,
 * space it and wrap it without anyone computing a coordinate. That is what
 * retires the `intdiv($width - $bounds['w'], 2) - $bounds['x1']` every sketch in
 * this repo currently open-codes, along with the baseline correction it needs to
 * be right for custom fonts.
 *
 * Alignment matters even though the box is usually exactly the text: a label
 * stretched by its parent, or auto-fitted to a smaller size than it asked for,
 * ends up with room to spare.
 */
class Label extends UXNode
{
    use DrawsText;

    public function __construct(string $text = '', ?Color $ink = null)
    {
        parent::__construct();

        $this->initialiseText($text, $ink);
    }

    public static function of(string $text, ?Color $ink = null): static
    {
        return new static($text, $ink);
    }

    public function measure(Constraints $constraints): Size
    {
        return $constraints->constrain($this->measureTextIn($constraints));
    }

    public function paint(DrawingSurface $surface): void
    {
        $this->paintText($surface, $this->localBounds());
    }
}
