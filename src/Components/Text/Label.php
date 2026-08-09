<?php

namespace ScrapyardIO\UX\Components\Text;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\TextAlign;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * A run of text that sizes itself to its own ink (classic 6x8 cell estimate).
 */
class Label extends UIComponent
{
    protected string $text;

    protected Color $ink;

    protected int $textSize;

    protected ?string $font;

    protected TextAlign $align;

    public function __construct(string $text = '', ?Color $ink = null)
    {
        parent::__construct();

        $this->text = $text;
        $this->ink = $ink ?? Theme::color('ink');
        $this->textSize = Theme::textSize();
        $this->font = Theme::font();
        $this->align = TextAlign::LEFT;
        $this->applyIntrinsicSize();
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
        if ($this->text === $text) {
            return $this;
        }

        $this->text = $text;
        $this->applyIntrinsicSize();

        return $this;
    }

    public function setColor(Color $ink): static
    {
        $this->ink = $ink;

        return $this;
    }

    public function color(): Color
    {
        return $this->ink;
    }

    public function setTextSize(int $size): static
    {
        $this->textSize = max(1, $size);
        $this->applyIntrinsicSize();

        return $this;
    }

    public function setFont(?string $font): static
    {
        $this->font = $font;
        $this->applyIntrinsicSize();

        return $this;
    }

    public function setAlign(TextAlign $align): static
    {
        $this->align = $align;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->text === '' || $this->ink->isTransparent()) {
            return;
        }

        $cellW = 6 * $this->textSize;
        $textW = $cellW * strlen($this->text);
        $x = match ($this->align) {
            TextAlign::LEFT => 0,
            TextAlign::CENTER => max(0, intdiv($this->rect->width - $textW, 2)),
            TextAlign::RIGHT => max(0, $this->rect->width - $textW),
        };

        $ctx->printLocal($x, 0, $this->text, $this->ink->pack(), null, $this->textSize, $this->font);
    }

    protected function applyIntrinsicSize(): void
    {
        $w = 6 * $this->textSize * strlen($this->text);
        $h = $this->text === '' ? 0 : 8 * $this->textSize;
        $this->setSize($w, $h);
    }
}
