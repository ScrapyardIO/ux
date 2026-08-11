<?php

namespace ScrapyardIO\UX\Components\Chrome;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Filled rectangle backdrop other nodes sit on.
 */
class Panel extends UIComponent
{
    protected Color $color;

    protected int $radius;

    public function __construct(?Color $color = null, int $radius = 0)
    {
        parent::__construct();

        $this->color = $color ?? Theme::color('panel');
        $this->radius = max(0, $radius);
    }

    public static function of(Color $color, int $radius = 0): static
    {
        return new static($color, $radius);
    }

    public static function surface(): static
    {
        return new static(Theme::color('surface'));
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

    public function radius(): int
    {
        return $this->radius;
    }

    public function setRadius(int $radius): static
    {
        $this->radius = max(0, $radius);

        return $this;
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        /** @var list<UIComponent> $ui */
        $ui = [];
        foreach ($this->children as $child) {
            if ($child instanceof UIComponent && $child->isVisible()) {
                $ui[] = $child;
            }
        }

        // Single child (e.g. Padding tray): fill the panel. Multiple children keep
        // their own rects — never blow Icons/Menus up to the full panel size.
        if (count($ui) === 1) {
            $child = $ui[0];
            $child->setPosition(0, 0);
            $child->setSize($this->rect->width, $this->rect->height);
            $child->layout(new Size($this->rect->width, $this->rect->height));

            return;
        }

        foreach ($ui as $child) {
            $child->layout($child->size()->isEmpty() ? $available : $child->size());
        }
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->color->isTransparent() || $this->rect->isEmpty()) {
            return;
        }

        $packed = $this->color->pack();

        if ($this->radius === 0) {
            $ctx->fillRectLocal(0, 0, $this->rect->width, $this->rect->height, $packed);

            return;
        }

        $ctx->fillRoundRectLocal(0, 0, $this->rect->width, $this->rect->height, $this->radius, $packed);
    }
}
