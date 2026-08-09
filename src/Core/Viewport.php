<?php

namespace ScrapyardIO\UX\Core;

use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Geometry\Rect;
use ScrapyardIO\UX\Geometry\Size;

/**
 * Visible window into the virtual world (size + scroll offset).
 */
final class Viewport
{
    public function __construct(
        protected Size $size = new Size(0, 0),
        protected Point $scroll = new Point(0, 0),
    ) {}

    public function size(): Size
    {
        return $this->size;
    }

    public function setSize(Size $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function scroll(): Point
    {
        return $this->scroll;
    }

    public function setScroll(Point $scroll): self
    {
        $this->scroll = $scroll;

        return $this;
    }

    public function scrollBy(int $dx, int $dy): self
    {
        $this->scroll = $this->scroll->translated($dx, $dy);

        return $this;
    }

    /**
     * Clip rect in buffer coordinates (always origin of the framebuffer view).
     */
    public function clipRect(): Rect
    {
        return new Rect(0, 0, $this->size->width, $this->size->height);
    }

    /**
     * World-space rectangle currently visible through this viewport.
     */
    public function worldVisibleRect(): Rect
    {
        return new Rect(
            $this->scroll->x,
            $this->scroll->y,
            $this->size->width,
            $this->size->height,
        );
    }
}
