<?php

namespace ScrapyardIO\UX\Controls;

use Closure;
use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Actuation\HumanInput\TouchPhase;
use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\InputTarget;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use Fabricate\UX\Concerns\ReceivesInput;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A two-state switch: a track with a knob at one end or the other.
 *
 * Kept purely geometric with no text of its own, because a caption belongs to
 * whatever row the toggle sits in — pairing them inside the node would fix the
 * caption's position relative to the switch, and on a narrow panel it usually
 * wants to be above rather than beside it.
 */
class Toggle extends UXNode implements InputTarget
{
    use ReceivesInput;

    protected bool $on = false;

    protected bool $pointer_down = false;

    protected Color $track;

    protected Color $knob;

    protected Color $active;

    protected int $extent;

    /**
     * @var Closure(bool, static): void|null
     */
    protected ?Closure $on_change = null;

    public function __construct(bool $on = false, ?Closure $on_change = null)
    {
        parent::__construct();

        $this->on = $on;
        $this->track = Theme::color('track');
        $this->knob = Theme::color('ink');
        $this->active = Theme::color('accent');
        $this->extent = Theme::metric('bar_thickness', 6) + 2;
        $this->on_change = $on_change;
    }

    public static function of(bool $on = false, ?Closure $on_change = null): static
    {
        return new static($on, $on_change);
    }

    public function isOn(): bool
    {
        return $this->on;
    }

    /**
     * @param  Closure(bool, static): void  $handler
     */
    public function onChange(Closure $handler): static
    {
        $this->on_change = $handler;

        return $this;
    }

    /**
     * Flipping the switch repaints its box and nothing else: the knob moves
     * inside a track whose size never changes.
     */
    public function setOn(bool $on): static
    {
        if ($this->on === $on) {
            return $this;
        }

        $this->on = $on;

        $this->invalidate();

        if (! is_null($this->on_change)) {
            ($this->on_change)($on, $this);
        }

        return $this;
    }

    public function toggle(): static
    {
        return $this->setOn(! $this->on);
    }

    public function setColors(?Color $active = null, ?Color $track = null, ?Color $knob = null): static
    {
        $this->active = $active ?? $this->active;
        $this->track = $track ?? $this->track;
        $this->knob = $knob ?? $this->knob;

        return $this->invalidate();
    }

    public function setExtent(int $extent): static
    {
        $extent = max(3, $extent);

        if ($this->extent === $extent) {
            return $this;
        }

        $this->extent = $extent;

        return $this->markNeedsLayout();
    }

    /**
     * Twice as wide as it is tall, the proportion that makes the two knob
     * positions readable at 8 pixels of height.
     */
    public function measure(Constraints $constraints): Size
    {
        return $this->intrinsic($constraints, $this->extent * 2, $this->extent);
    }

    public function paint(DrawingSurface $surface): void
    {
        $box = $this->localBounds();

        if (($box->width < 2) || ($box->height < 2)) {
            return;
        }

        $radius = intdiv($box->height, 2);

        $surface->fillRoundRect(
            0,
            0,
            $box->width,
            $box->height,
            $radius,
            $this->packed($this->on ? $this->active : $this->track),
        );

        if ($this->focused) {
            $surface->drawRoundRect(0, 0, $box->width, $box->height, $radius, $this->packed($this->knob));
        }

        $knob = max(1, $radius - 1);
        $centre = $this->on ? ($box->width - $radius - 1) : $radius;

        $surface->fillCircle($centre, $radius, $knob, $this->packed($this->knob));
    }

    public function onTouch(TouchContact $contact, Point $local): bool
    {
        if (($contact->phase === TouchPhase::ENDED) && $this->covers($local)) {
            $this->toggle();
        }

        return true;
    }

    /**
     * A pointer reports its buttons as a level, not an edge, so a held mouse
     * would otherwise flip the switch once per frame. The transition is what
     * counts.
     */
    public function onPointer(Point $local, bool $pressed): bool
    {
        if ($this->pointer_down === $pressed) {
            return $pressed;
        }

        $this->pointer_down = $pressed;

        if (! $pressed) {
            return false;
        }

        $this->toggle();

        return true;
    }

    public function onButton(string $label): bool
    {
        $this->toggle();

        return true;
    }
}
