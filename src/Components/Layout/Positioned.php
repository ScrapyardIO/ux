<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

/**
 * Absolute x/y wrapper for a child (used inside {@see Stack} or freely).
 */
class Positioned extends UIComponent
{
    protected int $posX;

    protected int $posY;

    public function __construct(int $x = 0, int $y = 0, ?UIComponent $child = null)
    {
        parent::__construct();

        $this->posX = $x;
        $this->posY = $y;

        if (! is_null($child)) {
            $this->addChild($child);
        }

        $this->setPosition($x, $y);
    }

    public static function at(int $x, int $y, ?UIComponent $child = null): static
    {
        return new static($x, $y, $child);
    }

    public function setXY(int $x, int $y): static
    {
        $this->posX = $x;
        $this->posY = $y;
        $this->setPosition($x, $y);

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
        $this->setPosition($this->posX, $this->posY);

        $child = $this->child();

        if (is_null($child)) {
            if ($this->rect->isEmpty()) {
                $this->setSize(0, 0);
            }

            return;
        }

        $child->setPosition(0, 0);
        $child->layout($child->size()->isEmpty() ? $available : $child->size());
        $this->setSize($child->size()->width, $child->size()->height);
    }

    protected function draw(PaintContext $ctx): void
    {
        //
    }
}
