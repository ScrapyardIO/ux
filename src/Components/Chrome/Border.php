<?php

namespace ScrapyardIO\UX\Components\Chrome;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Outline drawn around an optional child, inset by its own thickness.
 */
class Border extends UIComponent
{
    protected Color $color;

    protected int $thickness;

    protected int $radius;

    public function __construct(?Color $color = null, int $thickness = 1, int $radius = 0, ?UIComponent $child = null)
    {
        parent::__construct();

        $this->color = $color ?? Theme::color('outline');
        $this->thickness = max(1, $thickness);
        $this->radius = max(0, $radius);

        if (! is_null($child)) {
            $this->addChild($child);
        }
    }

    public static function around(UIComponent $child, ?Color $color = null, int $thickness = 1): static
    {
        return new static($color, $thickness, 0, $child);
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

    public function thickness(): int
    {
        return $this->thickness;
    }

    public function setThickness(int $thickness): static
    {
        $this->thickness = max(1, $thickness);

        return $this;
    }

    public function setRadius(int $radius): static
    {
        $this->radius = max(0, $radius);

        return $this;
    }

    public function child(): ?UIComponent
    {
        foreach ($this->children as $child) {
            if ($child instanceof UIComponent && $child->isVisible()) {
                return $child;
            }
        }

        return null;
    }

    public function setChild(?UIComponent $child): static
    {
        $this->clearChildren();

        return is_null($child) ? $this : $this->addChild($child);
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $child = $this->child();

        if (is_null($child)) {
            return;
        }

        $innerW = max(0, $this->rect->width - (2 * $this->thickness));
        $innerH = max(0, $this->rect->height - (2 * $this->thickness));

        $child->setPosition($this->thickness, $this->thickness);
        $child->setSize($innerW, $innerH);
        $child->layout(new Size($innerW, $innerH));
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->color->isTransparent() || $this->rect->isEmpty()) {
            return;
        }

        $packed = $this->color->pack();

        for ($ring = 0; $ring < $this->thickness; $ring++) {
            $width = $this->rect->width - (2 * $ring);
            $height = $this->rect->height - (2 * $ring);

            if ($width <= 0 || $height <= 0) {
                return;
            }

            $ctx->drawRectLocal($ring, $ring, $width, $height, $packed);
        }
    }
}
