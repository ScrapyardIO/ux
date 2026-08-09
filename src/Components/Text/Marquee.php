<?php

namespace ScrapyardIO\UX\Components\Text;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Text too wide for its box, scrolled sideways. Offset is paint-only.
 */
class Marquee extends UIComponent
{
    protected string $text;

    protected Color $ink;

    protected int $textSize;

    protected ?string $font;

    protected int $offset = 0;

    protected int $separation = 12;

    public function __construct(string $text = '', ?Color $ink = null)
    {
        parent::__construct();

        $this->text = $text;
        $this->ink = $ink ?? Theme::color('ink');
        $this->textSize = Theme::textSize();
        $this->font = Theme::font();
        $this->setSize(0, $this->text === '' ? 0 : 8 * $this->textSize);
    }

    public static function of(string $text, ?Color $ink = null): static
    {
        return new static($text, $ink);
    }

    public function text(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        $this->setSize($this->rect->width, $text === '' ? 0 : 8 * $this->textSize);

        return $this;
    }

    public function setColor(Color $ink): static
    {
        $this->ink = $ink;

        return $this;
    }

    public function setTextSize(int $size): static
    {
        $this->textSize = max(1, $size);
        $this->setSize($this->rect->width, $this->text === '' ? 0 : 8 * $this->textSize);

        return $this;
    }

    public function setFont(?string $font): static
    {
        $this->font = $font;

        return $this;
    }

    public function setSeparation(int $separation): static
    {
        $this->separation = max(0, $separation);

        return $this;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function advance(int $step = 1): static
    {
        if (! $this->isScrolling()) {
            return $this->reset();
        }

        return $this->setOffset($this->offset + $step);
    }

    public function setOffset(int $offset): static
    {
        $cycle = $this->cycle();
        $this->offset = ($cycle === 0) ? 0 : (($offset % $cycle) + $cycle) % $cycle;

        return $this;
    }

    public function reset(): static
    {
        return $this->setOffset(0);
    }

    public function isScrolling(): bool
    {
        return $this->textExtent() > $this->rect->width;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->text === '' || $this->ink->isTransparent()) {
            return;
        }

        $ink = $this->ink->pack();
        $y = max(0, intdiv($this->rect->height - (8 * $this->textSize), 2));

        if (! $this->isScrolling()) {
            $ctx->printLocal(0, $y, $this->text, $ink, null, $this->textSize, $this->font);

            return;
        }

        $cycle = $this->cycle();
        $ctx->printLocal(-$this->offset, $y, $this->text, $ink, null, $this->textSize, $this->font);
        $ctx->printLocal($cycle - $this->offset, $y, $this->text, $ink, null, $this->textSize, $this->font);
    }

    protected function textExtent(): int
    {
        return 6 * $this->textSize * strlen($this->text);
    }

    protected function cycle(): int
    {
        return $this->textExtent() + $this->separation;
    }
}
