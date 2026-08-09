<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

/**
 * Positions a shrink-wrapped child inside the available rect using 0..1 anchors.
 */
class Align extends UIComponent
{
    protected float $x;

    protected float $y;

    public function __construct(float $x = 0.5, float $y = 0.5, ?UIComponent $child = null)
    {
        parent::__construct();

        $this->x = $this->clampUnit($x);
        $this->y = $this->clampUnit($y);

        if (! is_null($child)) {
            $this->addChild($child);
        }
    }

    public static function centered(?UIComponent $child = null): static
    {
        return new static(0.5, 0.5, $child);
    }

    public static function topLeft(?UIComponent $child = null): static
    {
        return new static(0.0, 0.0, $child);
    }

    public function setAlignment(float $x, float $y): static
    {
        $this->x = $this->clampUnit($x);
        $this->y = $this->clampUnit($y);

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

        $child->layout($child->size()->isEmpty() ? $available : $child->size());
        $childSize = $child->size();

        $x = (int) round(($this->rect->width - $childSize->width) * $this->x);
        $y = (int) round(($this->rect->height - $childSize->height) * $this->y);

        $child->setPosition(max(0, $x), max(0, $y));
    }

    protected function draw(PaintContext $ctx): void
    {
        //
    }
}
