<?php

namespace ScrapyardIO\UX\Controls;

use Closure;
use Fabricate\Contracts\Actuation\HumanInput\GameControllerButton;
use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\Contracts\UX\InputTarget;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use Fabricate\UX\Concerns\ReceivesInput;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A rail with a thumb, either driven by input or reporting a value from
 * hardware.
 *
 * Both uses matter here. An on-screen control takes touch and pointer positions;
 * a physical potentiometer or a Seesaw NeoSlider writes its reading in with
 * {@see setValue()} and calls {@see focusable(false)} so a d-pad does not try to
 * move something the hand is already holding.
 *
 * The thumb overhangs the rail, so the node reserves a full thumb diameter of
 * height and centres the rail inside it — otherwise the thumb would be clipped
 * at the top and bottom of its own bounds.
 */
class Slider extends UXNode implements InputTarget
{
    use ReceivesInput;

    protected float $value = 0.0;

    protected float $step = 0.05;

    protected Axis $axis;

    protected Color $track;

    protected Color $fill;

    protected Color $thumb;

    protected int $rail;

    protected int $thumb_radius;

    /**
     * @var Closure(float, static): void|null
     */
    protected ?Closure $on_change = null;

    public function __construct(float $value = 0.0, Axis $axis = Axis::HORIZONTAL)
    {
        parent::__construct();

        $this->value = $this->clampUnit($value);
        $this->axis = $axis;
        $this->track = Theme::color('track');
        $this->fill = Theme::color('accent');
        $this->thumb = Theme::color('ink');
        $this->rail = Theme::metric('bar_thickness', 6);
        $this->thumb_radius = Theme::metric('thumb_radius', 4);
    }

    public static function of(float $value, Axis $axis = Axis::HORIZONTAL): static
    {
        return new static($value, $axis);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $value = $this->clampUnit($value);

        if ($this->value === $value) {
            return $this;
        }

        $this->value = $value;

        return $this->invalidate();
    }

    /**
     * @param  Closure(float, static): void  $handler
     */
    public function onChange(Closure $handler): static
    {
        $this->on_change = $handler;

        return $this;
    }

    public function setStep(float $step): static
    {
        $this->step = max(0.0, min(1.0, $step));

        return $this;
    }

    public function nudge(float $delta): static
    {
        return $this->commit($this->value + $delta);
    }

    public function setColors(?Color $fill = null, ?Color $track = null, ?Color $thumb = null): static
    {
        $this->fill = $fill ?? $this->fill;
        $this->track = $track ?? $this->track;
        $this->thumb = $thumb ?? $this->thumb;

        return $this->invalidate();
    }

    public function setRail(int $rail, ?int $thumb_radius = null): static
    {
        $this->rail = max(1, $rail);
        $this->thumb_radius = max(1, $thumb_radius ?? $this->thumb_radius);

        return $this->markNeedsLayout();
    }

    public function axis(): Axis
    {
        return $this->axis;
    }

    public function setAxis(Axis $axis): static
    {
        if ($this->axis === $axis) {
            return $this;
        }

        $this->axis = $axis;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        $thickness = max($this->rail, $this->thumb_radius * 2);

        return $this->spanning($constraints, $this->axis, $this->thumb_radius * 12, $thickness);
    }

    public function paint(DrawingSurface $surface): void
    {
        $box = $this->localBounds();

        if ($box->isEmpty()) {
            return;
        }

        $horizontal = $this->axis === Axis::HORIZONTAL;
        $length = $horizontal ? $box->width : $box->height;
        $thickness = $horizontal ? $box->height : $box->width;

        // Inset by the thumb radius so the thumb's own centre can reach either
        // end without any part of it leaving the node.
        $margin = min($this->thumb_radius, intdiv($length, 2));
        $travel = max(0, $length - (2 * $margin));
        $offset = $margin + (int) round($travel * $this->value);

        $rail = min($this->rail, $thickness);
        $rail_offset = intdiv($thickness - $rail, 2);
        $radius = intdiv($rail, 2);

        if ($horizontal) {
            $surface->fillRoundRect(0, $rail_offset, $box->width, $rail, $radius, $this->packed($this->track));
            $surface->fillRoundRect(0, $rail_offset, max(1, $offset), $rail, $radius, $this->packed($this->fill));
            $surface->fillCircle($offset, intdiv($box->height, 2), $this->thumbRadius($thickness), $this->packed($this->thumb));
        } else {
            $filled = max(1, $box->height - $offset);

            $surface->fillRoundRect($rail_offset, 0, $rail, $box->height, $radius, $this->packed($this->track));
            $surface->fillRoundRect($rail_offset, $offset, $rail, $filled, $radius, $this->packed($this->fill));
            $surface->fillCircle(intdiv($box->width, 2), $offset, $this->thumbRadius($thickness), $this->packed($this->thumb));
        }

        if ($this->focused) {
            $surface->drawRect(0, 0, $box->width, $box->height, $this->packed($this->fill));
        }
    }

    public function onTouch(TouchContact $contact, Point $local): bool
    {
        return $this->seekTo($local);
    }

    public function onPointer(Point $local, bool $pressed): bool
    {
        return $pressed && $this->seekTo($local);
    }

    /**
     * Step by one increment, wrapping at the top.
     *
     * The default bindings hand every direction to focus traversal, so what
     * normally reaches a focused node is the activation button alone. Stepping
     * and wrapping is therefore the only scheme that lets a two-button device
     * reach every value; a pad that rebinds a direction to activation gets the
     * expected decrement for free.
     */
    public function onButton(string $label): bool
    {
        $backwards = in_array($label, [
            'left',
            'up',
            'previous',
            GameControllerButton::DPAD_LEFT->value,
            GameControllerButton::DPAD_UP->value,
        ], true);

        $value = $this->value + ($backwards ? -$this->step : $this->step);

        $this->commit(match (true) {
            $value > 1.0 => 0.0,
            $value < 0.0 => 1.0,
            default => $value,
        });

        return true;
    }

    protected function thumbRadius(int $thickness): int
    {
        return max(1, min($this->thumb_radius, intdiv($thickness, 2)));
    }

    protected function seekTo(Point $local): bool
    {
        $box = $this->localBounds();
        $horizontal = $this->axis === Axis::HORIZONTAL;
        $length = $horizontal ? $box->width : $box->height;
        $margin = min($this->thumb_radius, intdiv($length, 2));
        $travel = max(1, $length - (2 * $margin));

        $position = ($horizontal ? $local->x : $local->y) - $margin;
        $fraction = $horizontal ? ($position / $travel) : (1.0 - ($position / $travel));

        $this->commit($fraction);

        return true;
    }

    protected function commit(float $value): static
    {
        $before = $this->value;

        $this->setValue($value);

        if (($this->value !== $before) && ! is_null($this->on_change)) {
            ($this->on_change)($this->value, $this);
        }

        return $this;
    }
}
