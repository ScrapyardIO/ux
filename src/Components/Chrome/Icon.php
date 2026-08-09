<?php

namespace ScrapyardIO\UX\Components\Chrome;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\IconGlyph;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * A small shape drawn from PaintContext primitives, sized by its box.
 */
class Icon extends UIComponent
{
    protected IconGlyph $glyph;

    protected Color $color;

    protected int $extent;

    protected ?int $height = null;

    public function __construct(IconGlyph $glyph = IconGlyph::DOT, int $extent = 8, ?Color $color = null)
    {
        parent::__construct();

        $this->glyph = $glyph;
        $this->extent = max(1, $extent);
        $this->color = $color ?? Theme::color('ink');
        $this->setSize($this->extent, $this->extent);
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
        $this->glyph = $glyph;

        return $this;
    }

    public function color(): Color
    {
        return $this->color;
    }

    public function setColor(Color $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function extent(): int
    {
        return $this->extent;
    }

    public function setExtent(int $extent): static
    {
        $this->extent = max(1, $extent);
        $this->setSize($this->extent, $this->height ?? $this->extent);

        return $this;
    }

    public function sized(int $width, int $height): static
    {
        $this->extent = max(1, $width);
        $this->height = max(1, $height);
        $this->setSize($this->extent, $this->height);

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->color->isTransparent() || $this->rect->isEmpty()) {
            return;
        }

        $ink = $this->color->pack();
        $right = $this->rect->width - 1;
        $bottom = $this->rect->height - 1;
        $centreX = intdiv($right, 2);
        $centreY = intdiv($bottom, 2);
        $radius = min($centreX, $centreY);
        $origin = $this->worldOrigin();
        $worldCx = $origin->x + $centreX;
        $worldCy = $origin->y + $centreY;

        match ($this->glyph) {
            IconGlyph::CIRCLE => $ctx->drawCircleWorld($worldCx, $worldCy, $radius, $ink),
            IconGlyph::DISC => $ctx->fillCircleWorld($worldCx, $worldCy, $radius, $ink),
            IconGlyph::DOT => $ctx->fillCircleWorld($worldCx, $worldCy, max(1, intdiv($radius, 2)), $ink),
            IconGlyph::SQUARE => $ctx->drawRectLocal(0, 0, $this->rect->width, $this->rect->height, $ink),
            IconGlyph::BOX => $ctx->fillRectLocal(0, 0, $this->rect->width, $this->rect->height, $ink),
            IconGlyph::TRIANGLE => $this->drawTriangle($ctx, $centreX, 0, 0, $bottom, $right, $bottom, $ink),
            IconGlyph::TRIANGLE_DOWN => $this->drawTriangle($ctx, 0, 0, $right, 0, $centreX, $bottom, $ink),
            IconGlyph::DIAMOND => $this->drawDiamond($ctx, $centreX, $centreY, $right, $bottom, $ink),
            IconGlyph::PLUS => $this->drawPlus($ctx, $centreX, $centreY, $ink),
            IconGlyph::CROSS => $this->drawCross($ctx, $right, $bottom, $ink),
            IconGlyph::CHEVRON_LEFT => $this->drawChevron($ctx, $right, 0, 0, $centreY, $right, $bottom, $ink),
            IconGlyph::CHEVRON_RIGHT => $this->drawChevron($ctx, 0, 0, $right, $centreY, 0, $bottom, $ink),
            IconGlyph::CHEVRON_UP => $this->drawChevron($ctx, 0, $bottom, $centreX, 0, $right, $bottom, $ink),
            IconGlyph::CHEVRON_DOWN => $this->drawChevron($ctx, 0, 0, $centreX, $bottom, $right, 0, $ink),
        };
    }

    protected function drawTriangle(PaintContext $ctx, int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $ink): void
    {
        $ctx->drawLineLocal($x0, $y0, $x1, $y1, $ink);
        $ctx->drawLineLocal($x1, $y1, $x2, $y2, $ink);
        $ctx->drawLineLocal($x2, $y2, $x0, $y0, $ink);
    }

    protected function drawDiamond(PaintContext $ctx, int $cx, int $cy, int $right, int $bottom, int $ink): void
    {
        $ctx->drawLineLocal($cx, 0, 0, $cy, $ink);
        $ctx->drawLineLocal(0, $cy, $cx, $bottom, $ink);
        $ctx->drawLineLocal($cx, $bottom, $right, $cy, $ink);
        $ctx->drawLineLocal($right, $cy, $cx, 0, $ink);
    }

    protected function drawPlus(PaintContext $ctx, int $cx, int $cy, int $ink): void
    {
        $ctx->drawLineLocal(0, $cy, $this->rect->width - 1, $cy, $ink);
        $ctx->drawLineLocal($cx, 0, $cx, $this->rect->height - 1, $ink);
    }

    protected function drawCross(PaintContext $ctx, int $right, int $bottom, int $ink): void
    {
        $ctx->drawLineLocal(0, 0, $right, $bottom, $ink);
        $ctx->drawLineLocal($right, 0, 0, $bottom, $ink);
    }

    protected function drawChevron(PaintContext $ctx, int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $ink): void
    {
        $ctx->drawLineLocal($x0, $y0, $x1, $y1, $ink);
        $ctx->drawLineLocal($x1, $y1, $x2, $y2, $ink);
    }
}
