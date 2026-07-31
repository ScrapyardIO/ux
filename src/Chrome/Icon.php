<?php

namespace ScrapyardIO\UX\Chrome;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Enums\IconGlyph;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A small shape drawn from renderer primitives, sized by its box.
 *
 * Everything is derived from the box rather than stored as coordinates, so
 * changing an icon's size is one setter and moving it is one call — which is the
 * whole reason a bouncing shape stops being forty lines of arithmetic and becomes
 * a node the sketch repositions.
 */
class Icon extends UXNode
{
    protected IconGlyph $glyph;

    protected Color $color;

    protected int $extent;

    /**
     * Null keeps the icon square, which is what nearly every glyph here wants.
     */
    protected ?int $height = null;

    public function __construct(IconGlyph $glyph = IconGlyph::DOT, int $extent = 8, ?Color $color = null)
    {
        parent::__construct();

        $this->glyph = $glyph;
        $this->extent = max(1, $extent);
        $this->color = $color ?? Theme::color('ink');
    }

    public static function of(IconGlyph $glyph, int $extent = 8, ?Color $color = null): static
    {
        return new static($glyph, $extent, $color);
    }

    public function glyph(): IconGlyph
    {
        return $this->glyph;
    }

    public function setGlyph(IconGlyph $glyph): static
    {
        if ($this->glyph === $glyph) {
            return $this;
        }

        $this->glyph = $glyph;

        return $this->invalidate();
    }

    public function color(): Color
    {
        return $this->color;
    }

    public function setColor(Color $color): static
    {
        $same = $this->indistinguishable($this->color, $color);

        $this->color = $color;

        return $same ? $this : $this->invalidate();
    }

    public function extent(): int
    {
        return $this->extent;
    }

    public function setExtent(int $extent): static
    {
        $extent = max(1, $extent);

        if ($this->extent === $extent) {
            return $this;
        }

        $this->extent = $extent;

        return $this->markNeedsLayout();
    }

    /**
     * An oblong box, for the glyphs that read as a shape rather than a symbol —
     * a BOX standing in for a bar, say.
     */
    public function sized(int $width, int $height): static
    {
        $width = max(1, $width);
        $height = max(1, $height);

        if (($this->extent === $width) && ($this->height === $height)) {
            return $this;
        }

        $this->extent = $width;
        $this->height = $height;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        return $this->intrinsic($constraints, $this->extent, $this->height ?? $this->extent);
    }

    public function paint(DrawingSurface $surface): void
    {
        if ($this->color->isTransparent()) {
            return;
        }

        $box = $this->localBounds();

        if ($box->isEmpty()) {
            return;
        }

        $ink = $this->packed($this->color);

        // Inclusive far edges: a primitive that takes a radius or a vertex needs
        // the last addressable pixel, not the exclusive extent.
        $right = $box->width - 1;
        $bottom = $box->height - 1;
        $centre_x = intdiv($right, 2);
        $centre_y = intdiv($bottom, 2);
        $radius = min($centre_x, $centre_y);

        match ($this->glyph) {
            IconGlyph::CIRCLE => $surface->drawCircle($centre_x, $centre_y, $radius, $ink),
            IconGlyph::DISC => $surface->fillCircle($centre_x, $centre_y, $radius, $ink),
            IconGlyph::DOT => $surface->fillCircle($centre_x, $centre_y, max(1, intdiv($radius, 2)), $ink),
            IconGlyph::SQUARE => $surface->drawRect(0, 0, $box->width, $box->height, $ink),
            IconGlyph::BOX => $surface->fillRect(0, 0, $box->width, $box->height, $ink),
            IconGlyph::TRIANGLE => $surface->drawTriangle($centre_x, 0, 0, $bottom, $right, $bottom, $ink),
            IconGlyph::TRIANGLE_DOWN => $surface->drawTriangle(0, 0, $right, 0, $centre_x, $bottom, $ink),
            IconGlyph::DIAMOND => $surface->drawTriangle($centre_x, 0, 0, $centre_y, $right, $centre_y, $ink)
                ->drawTriangle(0, $centre_y, $right, $centre_y, $centre_x, $bottom, $ink),
            IconGlyph::PLUS => $surface->drawHorizontalLine(0, $centre_y, $box->width, $ink)
                ->drawVerticalLine($centre_x, 0, $box->height, $ink),
            IconGlyph::CROSS => $surface->drawLine(0, 0, $right, $bottom, $ink)
                ->drawLine($right, 0, 0, $bottom, $ink),
            IconGlyph::CHEVRON_LEFT => $surface->drawLine($right, 0, 0, $centre_y, $ink)
                ->drawLine(0, $centre_y, $right, $bottom, $ink),
            IconGlyph::CHEVRON_RIGHT => $surface->drawLine(0, 0, $right, $centre_y, $ink)
                ->drawLine($right, $centre_y, 0, $bottom, $ink),
            IconGlyph::CHEVRON_UP => $surface->drawLine(0, $bottom, $centre_x, 0, $ink)
                ->drawLine($centre_x, 0, $right, $bottom, $ink),
            IconGlyph::CHEVRON_DOWN => $surface->drawLine(0, 0, $centre_x, $bottom, $ink)
                ->drawLine($centre_x, $bottom, $right, 0, $ink),
        };
    }

    public function isOpaque(): bool
    {
        return $this->glyph->isSolid() && $this->color->isOpaque();
    }
}
