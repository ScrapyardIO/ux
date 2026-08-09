<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

/**
 * Flex child that expands to fill leftover main-axis space.
 */
class Expanded extends UIComponent
{
    protected int $weight;

    public function __construct(?UIComponent $child = null, int $weight = 1)
    {
        parent::__construct();

        $this->weight = max(0, $weight);

        if (! is_null($child)) {
            $this->addChild($child);
        }
    }

    public static function of(?UIComponent $child = null, int $weight = 1): static
    {
        return new static($child, $weight);
    }

    public function weight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = max(0, $weight);

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

        $child->setPosition(0, 0);
        $child->setSize($this->rect->width, $this->rect->height);
        $child->layout(new Size($this->rect->width, $this->rect->height));
    }

    protected function draw(PaintContext $ctx): void
    {
        //
    }
}
