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
use ScrapyardIO\UX\Concerns\DrawsText;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A labelled box that reports being activated.
 *
 * Drawn as one node rather than composed from a panel and a label, because the
 * three states a button has — idle, focused, pressed — all change the box and
 * the text together, and splitting them across two nodes would mean two damage
 * reports for one visual change.
 *
 * Activation arrives from three directions and is deliberately treated as one
 * thing: a touch release inside the bounds, a pointer press, or a bound button
 * label while this node holds focus. A game controller, a touchscreen and four
 * switches on a breadboard therefore all drive the same handler.
 */
class Button extends UXNode implements InputTarget
{
    use DrawsText;
    use ReceivesInput;

    protected Color $face;

    protected Color $focus_ring;

    protected int $padding;

    protected int $radius;

    protected bool $pressed = false;

    /**
     * @var Closure(static): void|null
     */
    protected ?Closure $on_press = null;

    public function __construct(string $text = '', ?Closure $on_press = null)
    {
        parent::__construct();

        $this->initialiseText($text);

        $this->face = Theme::color('panel');
        $this->focus_ring = Theme::color('accent');
        $this->padding = Theme::metric('gap', 2) + 1;
        $this->radius = Theme::metric('radius', 0);
        $this->on_press = $on_press;
    }

    public static function of(string $text, ?Closure $on_press = null): static
    {
        return new static($text, $on_press);
    }

    /**
     * @param  Closure(static): void  $handler
     */
    public function onPress(Closure $handler): static
    {
        $this->on_press = $handler;

        return $this;
    }

    public function isPressed(): bool
    {
        return $this->pressed;
    }

    public function setFace(Color $face): static
    {
        if ($this->face->equals($face)) {
            return $this;
        }

        $this->face = $face;

        return $this->invalidate();
    }

    public function setFocusRing(Color $color): static
    {
        if ($this->focus_ring->equals($color)) {
            return $this;
        }

        $this->focus_ring = $color;

        return $this->invalidate();
    }

    public function setPadding(int $padding): static
    {
        $padding = max(0, $padding);

        if ($this->padding === $padding) {
            return $this;
        }

        $this->padding = $padding;

        return $this->markNeedsLayout();
    }

    public function setRadius(int $radius): static
    {
        $radius = max(0, $radius);

        if ($this->radius === $radius) {
            return $this;
        }

        $this->radius = $radius;

        return $this->invalidate();
    }

    public function measure(Constraints $constraints): Size
    {
        $text = $this->measureTextIn($constraints);

        return $constraints->constrain(new Size(
            $text->width + (2 * $this->padding),
            $text->height + (2 * $this->padding),
        ));
    }

    public function paint(DrawingSurface $surface): void
    {
        $box = $this->localBounds();

        if ($box->isEmpty()) {
            return;
        }

        // Pressed inverts face and ink rather than shading them, because a
        // monochrome panel has no shades to darken with.
        $face = $this->pressed ? $this->ink : $this->face;
        $packed_face = $this->packed($face);

        if ($this->radius === 0) {
            $surface->fillRect(0, 0, $box->width, $box->height, $packed_face);
        } else {
            $surface->fillRoundRect(0, 0, $box->width, $box->height, $this->radius, $packed_face);
        }

        if ($this->focused) {
            $ring = $this->packed($this->focus_ring);

            $this->radius === 0
                ? $surface->drawRect(0, 0, $box->width, $box->height, $ring)
                : $surface->drawRoundRect(0, 0, $box->width, $box->height, $this->radius, $ring);
        }

        $this->paintText($surface, $box, null, $this->pressed ? $this->face : null);
    }

    public function isOpaque(): bool
    {
        return $this->face->isOpaque() && ($this->radius === 0);
    }

    /**
     * A contact activates on release inside the bounds, which is what lets a
     * finger slide off a button to cancel — the standard escape hatch on a
     * touchscreen with no hover state.
     */
    public function onTouch(TouchContact $contact, Point $local): bool
    {
        if ($contact->phase === TouchPhase::BEGAN) {
            $this->setPressed(true);

            return true;
        }

        if (($contact->phase !== TouchPhase::ENDED) && ($contact->phase !== TouchPhase::CANCELLED)) {
            return true;
        }

        $was_pressed = $this->pressed;
        $this->setPressed(false);

        if ($was_pressed && ($contact->phase === TouchPhase::ENDED) && $this->covers($local)) {
            $this->activate();
        }

        return true;
    }

    public function onPointer(Point $local, bool $pressed): bool
    {
        if ($pressed === $this->pressed) {
            return $pressed;
        }

        $was_pressed = $this->pressed;

        $this->setPressed($pressed);

        if (! $pressed && $was_pressed && $this->covers($local)) {
            $this->activate();
        }

        return true;
    }

    public function onButton(string $label): bool
    {
        $this->activate();

        return true;
    }

    public function activate(): static
    {
        if (! is_null($this->on_press)) {
            ($this->on_press)($this);
        }

        return $this;
    }

    protected function setPressed(bool $pressed): void
    {
        if ($this->pressed === $pressed) {
            return;
        }

        $this->pressed = $pressed;

        $this->invalidate();
    }
}
