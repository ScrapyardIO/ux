<?php

namespace ScrapyardIO\UX\Components;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\BallBoost;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Filled circle with optional Arena-bounded physics (velocity, bounce, click boost).
 *
 * Centres are stored in parent-local floats; {@see process()} integrates and bounces
 * when parent is an {@see Arena}.
 */
class Ball extends UIComponent
{
    protected Color $fill;

    protected Color $outline;

    protected int $radius;

    protected float $cx = 0.0;

    protected float $cy = 0.0;

    protected float $vx = 0.0;

    protected float $vy = 0.0;

    protected float $ax = 0.0;

    protected float $ay = 0.0;

    protected float $boostUntil = 0.0;

    protected float $facingX = 1.0;

    protected float $facingY = 0.0;

    protected bool $mouseWasPressed = false;

    protected bool $physicsEnabled = false;

    protected ?Arena $arena = null;

    /** Last painted world centre — erased before the next draw on dirty/partial paths. */
    protected ?int $lastPaintCx = null;

    protected ?int $lastPaintCy = null;

    public function __construct(int $radius = 24, ?Color $fill = null, ?Color $outline = null)
    {
        parent::__construct('ball');

        $this->radius = max(1, $radius);
        $this->fill = $fill ?? Theme::color('accent');
        $this->outline = $outline ?? Theme::color('ink');
        $diameter = $this->radius * 2;
        $this->setSize($diameter, $diameter);
    }

    public static function of(int $radius = 24, ?Color $fill = null): static
    {
        return new static($radius, $fill);
    }

    public function setArena(?Arena $arena): static
    {
        $this->arena = $arena;

        return $this;
    }

    public function enablePhysics(float $vx = 426.0, float $vy = 144.0): static
    {
        $this->physicsEnabled = true;
        $this->vx = $vx;
        $this->vy = $vy;
        $this->facingX = $vx;
        $this->facingY = $vy;

        return $this;
    }

    public function radius(): int
    {
        return $this->radius;
    }

    public function setRadius(int $radius): static
    {
        $radius = max(1, $radius);

        if ($this->radius === $radius) {
            return $this;
        }

        $this->radius = $radius;
        $diameter = $this->radius * 2;
        $this->setSize($diameter, $diameter);
        $this->syncRectFromCenter();

        return $this;
    }

    public function fillColor(): Color
    {
        return $this->fill;
    }

    public function setFill(Color $fill): static
    {
        if ($this->fill->equals($fill)) {
            return $this;
        }

        $this->fill = $fill;

        return $this;
    }

    public function setOutline(Color $outline): static
    {
        if ($this->outline->equals($outline)) {
            return $this;
        }

        $this->outline = $outline;

        return $this;
    }

    public function setCenter(int $x, int $y): static
    {
        $this->cx = (float) $x;
        $this->cy = (float) $y;
        $this->syncRectFromCenter();

        return $this;
    }

    public function setCenterF(float $x, float $y): static
    {
        $this->cx = $x;
        $this->cy = $y;
        $this->syncRectFromCenter();

        return $this;
    }

    public function centerX(): int
    {
        return (int) round($this->cx);
    }

    public function centerY(): int
    {
        return (int) round($this->cy);
    }

    public function centerXF(): float
    {
        return $this->cx;
    }

    public function centerYF(): float
    {
        return $this->cy;
    }

    public function vx(): float
    {
        return $this->vx;
    }

    public function vy(): float
    {
        return $this->vy;
    }

    public function ax(): float
    {
        return $this->ax;
    }

    public function ay(): float
    {
        return $this->ay;
    }

    public function speed(): float
    {
        return hypot($this->vx, $this->vy);
    }

    public function boostRemaining(?float $now = null): float
    {
        $now ??= microtime(true);

        return max(0.0, $this->boostUntil - $now);
    }

    public function boostUnit(?float $now = null): float
    {
        $duration = BallBoost::DURATION_MS->floatValue() / 1000.0;

        if ($duration <= 0.0) {
            return 0.0;
        }

        return max(0.0, min(1.0, $this->boostRemaining($now) / $duration));
    }

    /**
     * Rising-edge left click in parent/Arena-local coordinates.
     */
    public function handlePointer(bool $pressed, float $localX, float $localY, ?float $now = null): void
    {
        $was = $this->mouseWasPressed;
        $this->mouseWasPressed = $pressed;

        if (! $pressed || $was) {
            return;
        }

        if (hypot($localX - $this->cx, $localY - $this->cy) > $this->radius) {
            return;
        }

        $now ??= microtime(true);
        $this->boostUntil = $now + (BallBoost::DURATION_MS->floatValue() / 1000.0);
        $this->facingX = abs($this->vx) + abs($this->vy) > BallBoost::SPEED_EPS->floatValue()
            ? $this->vx
            : 1.0;
        $this->facingY = abs($this->vx) + abs($this->vy) > BallBoost::SPEED_EPS->floatValue()
            ? $this->vy
            : 0.0;
    }

    public function process(float $dt): void
    {
        if (! $this->physicsEnabled || ! $this->visible) {
            parent::process($dt);

            return;
        }

        $dt = max(1.0 / 240.0, min(0.05, $dt));
        $prevVx = $this->vx;
        $prevVy = $this->vy;
        $now = microtime(true);

        $boostAx = 0.0;
        $boostAy = 0.0;

        if ($this->boostRemaining($now) > 0.0) {
            [$boostAx, $boostAy] = $this->boostAcceleration();
            if ($this->speed() > BallBoost::SPEED_EPS->floatValue()) {
                $this->facingX = $this->vx;
                $this->facingY = $this->vy;
            }
        }

        $this->vx += $boostAx * $dt;
        $this->vy += $boostAy * $dt;
        $this->cx += $this->vx * $dt;
        $this->cy += $this->vy * $dt;

        $this->bounceInArena();

        $this->ax = ($this->vx - $prevVx) / $dt;
        $this->ay = ($this->vy - $prevVy) / $dt;

        $this->fill = $this->accentForSpeed($this->speed());
        $this->syncRectFromCenter();

        parent::process($dt);
    }

    protected function draw(PaintContext $ctx): void
    {
        $origin = $this->worldOrigin();
        $cx = $origin->x + $this->radius;
        $cy = $origin->y + $this->radius;

        // Partial/dirty framebuffers: erase previous splat so Scene can skip full clear.
        if (! is_null($this->lastPaintCx) && ! is_null($this->lastPaintCy)) {
            if ($this->lastPaintCx !== $cx || $this->lastPaintCy !== $cy) {
                $ctx->fillCircleWorld(
                    $this->lastPaintCx,
                    $this->lastPaintCy,
                    $this->radius + 1,
                    Theme::color('surface')->pack(),
                );
            }
        }

        if (! $this->fill->isTransparent()) {
            $ctx->fillCircleWorld($cx, $cy, $this->radius, $this->fill->pack());
        }

        if (! $this->outline->isTransparent()) {
            $ctx->drawCircleWorld($cx, $cy, $this->radius, $this->outline->pack());
        }

        $this->lastPaintCx = $cx;
        $this->lastPaintCy = $cy;
    }

    protected function syncRectFromCenter(): void
    {
        $this->setPosition(
            (int) round($this->cx - $this->radius),
            (int) round($this->cy - $this->radius),
        );
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function boostAcceleration(): array
    {
        $a = BallBoost::ACCEL_PX_S2->floatValue();
        $speed = $this->speed();
        $eps = BallBoost::SPEED_EPS->floatValue();

        if ($speed > $eps) {
            return [($this->vx / $speed) * $a, ($this->vy / $speed) * $a];
        }

        $face = hypot($this->facingX, $this->facingY);
        if ($face <= 0.0) {
            return [$a, 0.0];
        }

        return [($this->facingX / $face) * $a, ($this->facingY / $face) * $a];
    }

    protected function bounceInArena(): void
    {
        $arena = $this->arena;
        if (is_null($arena)) {
            return;
        }

        $field = $arena->playfield();
        $r = (float) $this->radius;
        $minX = $r;
        $maxX = (float) max($r, $field->width - $r - 1);
        $minY = $r;
        $maxY = (float) max($r, $field->height - $r - 1);
        $restitution = $arena->restitution();

        $penLeft = $minX - $this->cx;
        $penRight = $this->cx - $maxX;
        $penTop = $minY - $this->cy;
        $penBottom = $this->cy - $maxY;

        $hitX = $penLeft > 0 || $penRight > 0;
        $hitY = $penTop > 0 || $penBottom > 0;

        if ($hitX && $hitY) {
            $penX = max($penLeft, $penRight);
            $penY = max($penTop, $penBottom);

            if ($penX >= $penY) {
                $this->bounceX($minX, $maxX, $restitution);
            } else {
                $this->bounceY($minY, $maxY, $restitution);
            }
        } else {
            if ($hitX) {
                $this->bounceX($minX, $maxX, $restitution);
            }
            if ($hitY) {
                $this->bounceY($minY, $maxY, $restitution);
            }
        }

        $this->cx = max($minX, min($maxX, $this->cx));
        $this->cy = max($minY, min($maxY, $this->cy));

        $eps = BallBoost::SPEED_EPS->floatValue();
        if (abs($this->vx) < $eps) {
            $this->vx = 0.0;
        }
        if (abs($this->vy) < $eps) {
            $this->vy = 0.0;
        }
    }

    protected function bounceX(float $minX, float $maxX, float $restitution): void
    {
        if ($this->cx < $minX) {
            $this->cx = $minX;
            $this->vx = abs($this->vx) * $restitution;
        } elseif ($this->cx > $maxX) {
            $this->cx = $maxX;
            $this->vx = -abs($this->vx) * $restitution;
        }
    }

    protected function bounceY(float $minY, float $maxY, float $restitution): void
    {
        if ($this->cy < $minY) {
            $this->cy = $minY;
            $this->vy = abs($this->vy) * $restitution;
        } elseif ($this->cy > $maxY) {
            $this->cy = $maxY;
            $this->vy = -abs($this->vy) * $restitution;
        }
    }

    /**
     * Cool blue → hot amber by speed (tubes MetalCanvasHud parity).
     */
    protected function accentForSpeed(float $speed, float $maxSpeed = 720.0): Color
    {
        $t = $maxSpeed > 0.0 ? max(0.0, min(1.0, $speed / $maxSpeed)) : 0.0;
        $r = (int) round(0x40 + $t * (0xF0 - 0x40));
        $g = (int) round(0x80 + $t * (0xA0 - 0x80));
        $b = (int) round(0xF0 + $t * (0x30 - 0xF0));

        return new Color($r, $g, $b);
    }
}
