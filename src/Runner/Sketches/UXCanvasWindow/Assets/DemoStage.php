<?php

namespace ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\Assets;

use ScrapyardIO\UX\Components\Arena;
use ScrapyardIO\UX\Components\Ball;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Sketch-local stage for {@see \ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\UxCanvasWindowDemo}:
 * {@see DemoHud} over an {@see Arena} that owns a physics {@see Ball}.
 *
 * Transparent root draw — Scene clear is the backdrop (avoid full-window Panel).
 */
class DemoStage extends UIComponent
{
    protected DemoHud $hud;

    protected Arena $arena;

    protected Ball $ball;

    protected int $hudHeight;

    protected bool $spawned = false;

    public function __construct(float $restitution = 0.85, int $ballRadius = 24, int $hudHeight = 72)
    {
        parent::__construct('demo-stage');

        $this->hudHeight = max(56, $hudHeight);
        $this->hud = DemoHud::of(2);
        // No per-frame arena chrome — outline/floor dirties almost the full panel on FT232H.
        $this->arena = Arena::of(0, 0, $restitution)
            ->setFloor(Color::transparent())
            ->setOutline(Color::transparent());
        $this->ball = Ball::of($ballRadius, Theme::color('accent'))
            ->enablePhysics(426.0, 144.0);
        $this->arena->setBall($this->ball);

        $this->addChild($this->hud);
        $this->addChild($this->arena);
    }

    public static function of(float $restitution = 0.85, int $ballRadius = 24): static
    {
        return new static($restitution, $ballRadius);
    }

    public function hud(): DemoHud
    {
        return $this->hud;
    }

    public function arena(): Arena
    {
        return $this->arena;
    }

    public function ball(): Ball
    {
        return $this->ball;
    }

    public function sync(float $fps, int $tick): bool
    {
        return $this->hud->sync($this->ball, $fps, $tick);
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $w = $this->rect->width;
        $h = $this->rect->height;
        $hudH = min($this->hudHeight, max(56, $h));

        $this->hud->setPosition(0, 0);
        $this->hud->setSize($w, $hudH);

        $arenaH = max(1, $h - $hudH);
        $this->arena->setPosition(0, $hudH);
        $this->arena->setSize($w, $arenaH);

        $this->hud->layout($this->hud->size());
        $this->arena->layout($this->arena->size());

        if (! $this->spawned && $w > 0 && $arenaH > 0) {
            $this->ball->setCenterF($w / 2.0, $arenaH / 2.0);
            $this->spawned = true;
        }
    }

    protected function draw(PaintContext $ctx): void
    {
        // Transparent stage — children paint; Scene clear fills the backdrop.
    }
}
