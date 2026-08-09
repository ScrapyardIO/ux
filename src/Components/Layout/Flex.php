<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\Axis;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Theme;

/**
 * Lays children out in a line along {@see Axis}, with optional flex grow.
 */
class Flex extends UIComponent
{
    protected Axis $axis;

    protected int $gap;

    public function __construct(Axis $axis = Axis::HORIZONTAL, int $gap = 0)
    {
        parent::__construct();

        $this->axis = $axis;
        $this->gap = max(0, $gap > 0 ? $gap : Theme::metric('gap', 2));
    }

    public static function horizontal(int $gap = 0): static
    {
        return new static(Axis::HORIZONTAL, $gap);
    }

    public static function vertical(int $gap = 0): static
    {
        return new static(Axis::VERTICAL, $gap);
    }

    public function axis(): Axis
    {
        return $this->axis;
    }

    public function gap(): int
    {
        return $this->gap;
    }

    public function setGap(int $gap): static
    {
        $this->gap = max(0, $gap);

        return $this;
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $children = $this->uiChildren();
        $count = count($children);

        if ($count === 0) {
            return;
        }

        $horizontal = $this->axis === Axis::HORIZONTAL;
        $mainAvail = $horizontal ? $this->rect->width : $this->rect->height;
        $crossAvail = $horizontal ? $this->rect->height : $this->rect->width;
        $gaps = $this->gap * max(0, $count - 1);

        $fixed = 0;
        $flexTotal = 0;
        $weights = [];

        foreach ($children as $index => $child) {
            $weight = $this->flexWeight($child);
            $weights[$index] = $weight;

            if ($weight > 0) {
                $flexTotal += $weight;
            } else {
                $fixed += $horizontal ? $child->size()->width : $child->size()->height;
            }
        }

        $free = max(0, $mainAvail - $gaps - $fixed);
        $granted = 0;
        $weighed = 0;
        $offset = 0;

        foreach ($children as $index => $child) {
            $weight = $weights[$index];
            $childSize = $child->size();

            if ($weight > 0 && $flexTotal > 0) {
                $weighed += $weight;
                $share = intdiv($free * $weighed, $flexTotal) - $granted;
                $granted += $share;

                if ($horizontal) {
                    $child->setSize($share, $crossAvail > 0 ? $crossAvail : $childSize->height);
                } else {
                    $child->setSize($crossAvail > 0 ? $crossAvail : $childSize->width, $share);
                }
            } elseif ($crossAvail > 0) {
                if ($horizontal) {
                    $child->setSize($childSize->width, $crossAvail);
                } else {
                    $child->setSize($crossAvail, $childSize->height);
                }
            }

            if ($horizontal) {
                $child->setPosition($offset, 0);
            } else {
                $child->setPosition(0, $offset);
            }

            $main = $horizontal ? $child->size()->width : $child->size()->height;
            $child->layout($child->size());
            $offset += $main + $this->gap;
        }
    }

    protected function draw(PaintContext $ctx): void
    {
        // Children paint themselves.
    }

    /**
     * @return list<UIComponent>
     */
    protected function uiChildren(): array
    {
        $out = [];

        foreach ($this->children as $child) {
            if ($child instanceof UIComponent && $child->isVisible()) {
                $out[] = $child;
            }
        }

        return $out;
    }

    protected function flexWeight(UIComponent $child): int
    {
        if ($child instanceof Expanded || $child instanceof Spacer) {
            return max(0, $child->weight());
        }

        return 0;
    }
}
