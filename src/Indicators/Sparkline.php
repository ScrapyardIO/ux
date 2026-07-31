<?php

namespace ScrapyardIO\UX\Indicators;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A rolling series drawn as a polyline across the node's box.
 *
 * Samples are normalised against the series' own range rather than against a
 * fixed scale, so a sensor whose readings sit in a narrow band still produces a
 * legible trace. A flat series is drawn along the middle instead of collapsing
 * onto an edge, which is what a naive min-max normalisation would do.
 *
 * The window is bounded and old samples are dropped, so a sketch that pushes
 * every frame does not grow an unbounded array behind the display.
 */
class Sparkline extends UXNode
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

    /**
     * Adding a sample only repaints: the trace moves inside a box whose size
     * never changes.
     */
    public function push(float $sample): static
    {
        $this->samples[] = $sample;

        if (count($this->samples) > $this->capacity) {
            $this->samples = array_slice($this->samples, -$this->capacity);
        }

        return $this->invalidate();
    }

    /**
     * @param  array<int, float>  $samples
     */
    public function setSamples(array $samples): static
    {
        $this->samples = array_slice(array_values($samples), -$this->capacity);

        return $this->invalidate();
    }

    public function clear(): static
    {
        if ($this->samples === []) {
            return $this;
        }

        $this->samples = [];

        return $this->invalidate();
    }

    public function setCapacity(int $capacity): static
    {
        $capacity = max(2, $capacity);

        if ($this->capacity === $capacity) {
            return $this;
        }

        $this->capacity = $capacity;
        $this->samples = array_slice($this->samples, -$capacity);

        return $this->invalidate();
    }

    public function setColor(Color $trace): static
    {
        if ($this->trace->equals($trace)) {
            return $this;
        }

        $this->trace = $trace;

        return $this->invalidate();
    }

    public function measure(Constraints $constraints): Size
    {
        return $this->spanning($constraints, Axis::HORIZONTAL, $this->capacity, $this->extent);
    }

    public function paint(DrawingSurface $surface): void
    {
        $box = $this->localBounds();
        $count = count($this->samples);

        if (($count === 0) || ($box->width < 2) || ($box->height < 1)) {
            return;
        }

        $ink = $this->packed($this->trace);

        if ($count === 1) {
            $surface->drawHorizontalLine(0, $this->rowFor($this->samples[0]), $box->width, $ink);

            return;
        }

        $step = ($box->width - 1) / ($count - 1);
        $lines = [];

        for ($index = 1; $index < $count; $index++) {
            $lines[] = [
                (int) round($step * ($index - 1)),
                $this->rowFor($this->samples[$index - 1]),
                (int) round($step * $index),
                $this->rowFor($this->samples[$index]),
                $ink,
            ];
        }

        $surface->drawLines($lines);
    }

    /**
     * Highest sample at the top, which means inverting: row 0 is the top of the
     * box and the largest value.
     */
    protected function rowFor(float $sample): int
    {
        $box = $this->localBounds();
        $low = min($this->samples);
        $span = max($this->samples) - $low;

        if ($span <= 0.0) {
            return intdiv($box->height - 1, 2);
        }

        return (int) round(($box->height - 1) * (1.0 - (($sample - $low) / $span)));
    }
}
