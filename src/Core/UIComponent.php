<?php

namespace ScrapyardIO\UX\Core;

use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Geometry\Rect;
use ScrapyardIO\UX\Geometry\Size;

/**
 * UI layer: parent-relative rect, layout pass, hit-test hooks.
 *
 * Engine gameplay nodes should prefer {@see Node} / {@see Drawable}, not this.
 */
abstract class UIComponent extends Drawable
{
    protected Rect $rect;

    protected ?Point $worldOriginCache = null;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name);

        $this->rect = $rect ?? Rect::empty();
    }

    public function rect(): Rect
    {
        return $this->rect;
    }

    public function setRect(Rect $rect): static
    {
        if ($this->rect->equals($rect)) {
            return $this;
        }

        $this->rect = $rect;
        $this->invalidateWorldTransform();

        return $this;
    }

    public function setPosition(int $x, int $y): static
    {
        if ($this->rect->x === $x && $this->rect->y === $y) {
            return $this;
        }

        $this->rect = new Rect($x, $y, $this->rect->width, $this->rect->height);
        $this->invalidateWorldTransform();

        return $this;
    }

    public function setSize(int $width, int $height): static
    {
        if ($this->rect->width === $width && $this->rect->height === $height) {
            return $this;
        }

        $this->rect = new Rect($this->rect->x, $this->rect->y, $width, $height);
        $this->invalidateWorldTransform();

        return $this;
    }

    public function size(): Size
    {
        return $this->rect->size();
    }

    /**
     * World rect = parent world origin + local rect.
     */
    public function worldRect(): ?Rect
    {
        $origin = $this->worldOrigin();

        return new Rect($origin->x, $origin->y, $this->rect->width, $this->rect->height);
    }

    public function worldOrigin(): Point
    {
        if (! is_null($this->worldOriginCache)) {
            return $this->worldOriginCache;
        }

        $x = $this->rect->x;
        $y = $this->rect->y;
        $parent = $this->parent;

        while (! is_null($parent)) {
            if ($parent instanceof UIComponent) {
                $parentOrigin = $parent->worldOrigin();
                $x += $parentOrigin->x;
                $y += $parentOrigin->y;

                break;
            }

            $parent = $parent->parent();
        }

        return $this->worldOriginCache = new Point($x, $y);
    }

    /**
     * Drop cached world origins for this subtree after a move/resize.
     */
    public function invalidateWorldTransform(): void
    {
        $this->worldOriginCache = null;

        foreach ($this->children as $child) {
            if ($child instanceof UIComponent) {
                $child->invalidateWorldTransform();
            }
        }
    }

    /**
     * Layout this component and its UI children. Default: leave child rects as set.
     */
    public function layout(Size $available): void
    {
        foreach ($this->children as $child) {
            if ($child instanceof UIComponent) {
                $child->layout($child->size()->isEmpty() ? $available : $child->size());
            }
        }
    }

    /**
     * Hit-test in local coordinates. Returns deepest UIComponent covering the point.
     */
    public function hitTest(int $localX, int $localY): ?UIComponent
    {
        if (! $this->visible || ! $this->covers($localX, $localY)) {
            return null;
        }

        for ($i = count($this->children) - 1; $i >= 0; $i--) {
            $child = $this->children[$i];

            if (! $child instanceof UIComponent || ! $child->isVisible()) {
                continue;
            }

            $hit = $child->hitTest($localX - $child->rect->x, $localY - $child->rect->y);

            if (! is_null($hit)) {
                return $hit;
            }
        }

        return $this;
    }

    protected function covers(int $localX, int $localY): bool
    {
        return $localX >= 0
            && $localY >= 0
            && $localX < $this->rect->width
            && $localY < $this->rect->height;
    }

    protected function clampUnit(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }

    public function paint(PaintContext $ctx): void
    {
        if (! $this->visible) {
            return;
        }

        $origin = $this->worldOrigin();
        $world = new Rect($origin->x, $origin->y, $this->rect->width, $this->rect->height);

        if ($ctx->cullRect($world)->isEmpty()) {
            return;
        }

        $this->draw($ctx->withOrigin($origin));
        $this->paintDescendants($this, $ctx);
    }
}
