<?php

namespace ScrapyardIO\UX\Text;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A large value with a small caption underneath it.
 *
 * This pairing is the thing instrument sketches keep re-deriving by hand: a
 * number at one text size, a unit or a description at another, both centred on
 * the same axis. Two labels in a column would nearly do it, except that a column
 * fills its main axis whenever the offer is bounded, so a readout inside a row
 * inside a column would swell to the height of the screen. Stacking the two
 * labels here instead keeps it shrink-wrapped, which is the only behaviour that
 * composes.
 *
 * Updating the value is a paint, not a relayout, whenever the new string
 * measures the same as the old — the common case for a fixed-width numeric
 * field, and the difference between a repaint of one box and a walk of the tree.
 */
class Readout extends UXNode
{
    protected Label $value;

    protected Label $caption;

    protected int $gap;

    public function __construct(string $value = '', string $caption = '', int $value_size = 2)
    {
        parent::__construct();

        $this->gap = Theme::metric('gap', 2);

        $this->value = Label::of($value)->setTextSize(max(1, $value_size));
        $this->caption = Label::of($caption, Theme::color('muted'));

        $this->add($this->value, $this->caption);
    }

    public static function of(string $value, string $caption = '', int $value_size = 2): static
    {
        return new static($value, $caption, $value_size);
    }

    public function value(): Label
    {
        return $this->value;
    }

    public function caption(): Label
    {
        return $this->caption;
    }

    public function setValue(string $value): static
    {
        $this->value->setText($value);

        return $this;
    }

    public function setCaption(string $caption): static
    {
        $this->caption->setText($caption);

        return $this;
    }

    public function setValueColor(Color $color): static
    {
        $this->value->setColor($color);

        return $this;
    }

    public function setCaptionColor(Color $color): static
    {
        $this->caption->setColor($color);

        return $this;
    }

    public function setGap(int $gap): static
    {
        $gap = max(0, $gap);

        if ($this->gap === $gap) {
            return $this;
        }

        $this->gap = $gap;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        $loose = $constraints->loosened();

        $value = $this->value->layout($loose);
        $caption = $this->caption->layout($loose);

        // No gap when one half is empty, so a readout with no caption is exactly
        // as tall as its value rather than carrying a stripe of nothing.
        $gap = ($value->isEmpty() || $caption->isEmpty()) ? 0 : $this->gap;

        $size = $constraints->constrain(new Size(
            max($value->width, $caption->width),
            $value->height + $gap + $caption->height,
        ));

        $this->value->placeAt($this->centred($size->width, $value->width), 0);
        $this->caption->placeAt($this->centred($size->width, $caption->width), $value->height + $gap);

        return $size;
    }

    public function paint(DrawingSurface $surface): void
    {
        //
    }

    protected function centred(int $available, int $extent): int
    {
        return max(0, intdiv($available - $extent, 2));
    }
}
