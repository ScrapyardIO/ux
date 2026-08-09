<?php

namespace ScrapyardIO\UX\Components;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Rect;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Bounded playfield: floor + outline, owns restitution for child {@see Ball} physics.
 *
 * Ball centres are in Arena-local coordinates; walls are the Arena rect inset by radius.
 */
class Arena extends UIComponent
{
    protected Color $floor;

    protected Color $outline;

    protected int $stroke;

    protected float $restitution;

    protected ?Ball $ball = null;

    public function __construct(?Color $floor = null, ?Color $outline = null, float $restitution = 0.85)
    {
        parent::__construct('arena');

        // Transparent by default — Scene clear owns the backdrop; Arena draws outline only
        // so Metal CPU paths are not forced into a near-fullscreen fillRect every frame.
        $this->floor = $floor ?? Color::transparent();
        $this->outline = $outline ?? Theme::color('outline');
        $this->stroke = max(1, Theme::metric('stroke', 1));
        $this->restitution = max(0.0, min(1.0, $restitution));
    }

    public static function of(int $width, int $height, float $restitution = 0.85): static
    {
        $arena = new static(restitution: $restitution);
        $arena->setSize($width, $height);

        return $arena;
    }

    public function restitution(): float
    {
        return $this->restitution;
    }

    public function setRestitution(float $restitution): static
    {
        $this->restitution = max(0.0, min(1.0, $restitution));

        return $this;
    }

    public function ball(): ?Ball
    {
        return $this->ball;
    }

    public function setBall(Ball $ball): static
    {
        if (! is_null($this->ball) && $this->ball !== $ball) {
            $this->removeChild($this->ball);
        }

        $this->ball = $ball;
        $ball->setArena($this);

        if (! in_array($ball, $this->children, true)) {
            $this->addChild($ball);
        }

        return $this;
    }

    /**
     * Inner playfield in local coordinates (full arena box).
     */
    public function playfield(): Rect
    {
        return new Rect(0, 0, $this->rect->width, $this->rect->height);
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        parent::layout($available);
    }

    protected function draw(PaintContext $ctx): void
    {
        if (! $this->floor->isTransparent() && ! $this->rect->isEmpty()) {
            $ctx->fillRectLocal(0, 0, $this->rect->width, $this->rect->height, $this->floor->pack());
        }

        if (! $this->outline->isTransparent() && ! $this->rect->isEmpty()) {
            for ($i = 0; $i < $this->stroke; $i++) {
                $ctx->drawRectLocal(
                    $i,
                    $i,
                    max(0, $this->rect->width - (2 * $i)),
                    max(0, $this->rect->height - (2 * $i)),
                    $this->outline->pack(),
                );
            }
        }
    }
}
