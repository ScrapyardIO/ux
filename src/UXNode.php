<?php

namespace ScrapyardIO\UX;

use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Node;
use ScrapyardIO\UX\Concerns\ResolvesColors;

/**
 * The base every node in this library extends.
 *
 * It adds two things to {@see Node} and deliberately nothing else. Colours are
 * declared as {@see \Fabricate\UX\Color} and packed against the surface the tree
 * is bound to, so one tree paints correctly on a 1-bit panel and an RGBA window.
 * And sizing gets the two shapes that keep recurring: a node with a natural size,
 * and a node that runs the length of its container with a fixed thickness.
 */
abstract class UXNode extends Node
{
    use ResolvesColors;

    /**
     * This node's own box, in the coordinates {@see paint()} works in.
     */
    protected function localBounds(): Rect
    {
        return $this->size()->atOrigin();
    }

    /**
     * A natural size, clamped into what the parent will accept.
     */
    protected function intrinsic(Constraints $constraints, int $width, int $height): Size
    {
        return $constraints->constrain(new Size($width, $height));
    }

    /**
     * Fill whichever axes the parent bounded, falling back to a natural size on
     * any axis it left open. This is what a background or a full-width bar wants.
     */
    protected function stretched(Constraints $constraints, int $width, int $height): Size
    {
        return $constraints->constrain(new Size(
            $constraints->hasBoundedWidth() ? $constraints->max_width : $width,
            $constraints->hasBoundedHeight() ? $constraints->max_height : $height,
        ));
    }

    /**
     * Run the length of the container along $axis and stay $thickness across it,
     * which is the shape of every bar, rail and strip here.
     */
    protected function spanning(Constraints $constraints, Axis $axis, int $length, int $thickness): Size
    {
        if ($axis === Axis::HORIZONTAL) {
            return $constraints->constrain(new Size(
                $constraints->hasBoundedWidth() ? $constraints->max_width : $length,
                $thickness,
            ));
        }

        return $constraints->constrain(new Size(
            $thickness,
            $constraints->hasBoundedHeight() ? $constraints->max_height : $length,
        ));
    }

    /**
     * Clamp to 0.0-1.0, since every normalised input here comes from an ADC, a
     * ratio or a caller, and none of the three can be trusted to stay in range.
     */
    protected function clampUnit(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }

    /**
     * Whether a local point still lies on this node.
     *
     * Worth asking on any event that commits to something. The router hands a
     * gesture back to the node it started on for the rest of its life, so a
     * release can arrive from well outside the box — and a control that acts on
     * it anyway fires when the user was plainly trying to cancel.
     */
    protected function covers(Point $local): bool
    {
        return $this->localBounds()->contains($local->x, $local->y);
    }
}
