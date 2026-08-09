<?php

namespace ScrapyardIO\UX\Components\Indicators;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * A rolling series drawn as a polyline across the node's box.
 */
class Sparkline extends UIComponent
{
    /**
     * @var array<int, float>
     */
    protected array $samples = [];

    protected int $capacity;

    protected Color $trace;

    protected int $extent;

    public function __construct(int $capacity = 0, ?Color $trace = null)
    {
        parent::__construct();

        $this->capacity = max(2, ($capacity > 0) ? $capacity : Theme::metric('sparkline_samples', 32));
        $this->trace = $trace ?? Theme::color('accent');
        $this->extent = Theme::metric('bar_thickness', 6) * 2;
        $this->setSize($this->capacity, $this->extent);
    }

    public static function of(int $capacity = 0, ?Color $trace = null): static
    {
        return new static($capacity, $trace);
    }

    /**
     * @return array<int, float>
     */
    public function samples(): array
    {
        return $this->samples;
    }

    public function push(float $sample): static
    {
        $this->samples[] = $sample;

        if (count($this->samples) > $this->capacity) {
            $this->samples = array_slice($this->samples, -$this->capacity);
        }

        return $this;
    }

    /**
     * @param  array<int, float>  $samples
     */
    public function setSamples(array $samples): static
    {
        $this->samples = array_slice(array_values($samples), -$this->capacity);

        return $this;
    }

    public function clear(): static
    {
        $this->samples = [];

        return $this;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = max(2, $capacity);
        $this->samples = array_slice($this->samples, -$this->capacity);
        $this->setSize($this->capacity, $this->extent);

        return $this;
    }

    public function setColor(Color $trace): static
    {
        $this->trace = $trace;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        $count = count($this->samples);

        if ($count === 0 || $this->rect->width < 2 || $this->rect->height < 1) {
            return;
        }

        $ink = $this->trace->pack();

        if ($count === 1) {
            $y = $this->rowFor($this->samples[0]);
            $ctx->drawLineLocal(0, $y, $this->rect->width - 1, $y, $ink);

            return;
        }

        $step = ($this->rect->width - 1) / ($count - 1);

        for ($index = 1; $index < $count; $index++) {
            $ctx->drawLineLocal(
                (int) round($step * ($index - 1)),
                $this->rowFor($this->samples[$index - 1]),
                (int) round($step * $index),
                $this->rowFor($this->samples[$index]),
                $ink,
            );
        }
    }

    protected function rowFor(float $sample): int
    {
        $low = min($this->samples);
        $span = max($this->samples) - $low;

        if ($span <= 0.0) {
            return intdiv($this->rect->height - 1, 2);
        }

        return (int) round(($this->rect->height - 1) * (1.0 - (($sample - $low) / $span)));
    }
}
