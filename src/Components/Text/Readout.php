<?php

namespace ScrapyardIO\UX\Components\Text;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * A large value with a small caption stacked underneath, shrink-wrapped.
 */
class Readout extends UIComponent
{
    protected Label $value;

    protected Label $caption;

    protected int $gap;

    public function __construct(string $value = '', string $caption = '', int $valueSize = 2)
    {
        parent::__construct();

        $this->gap = Theme::metric('gap', 2);

        $this->value = Label::of($value)->setTextSize(max(1, $valueSize));
        $this->caption = Label::of($caption, Theme::color('muted'));

        $this->addChild($this->value);
        $this->addChild($this->caption);

        $this->applyIntrinsicSize();
    }

    public static function of(string $value, string $caption = '', int $valueSize = 2): static
    {
        return new static($value, $caption, $valueSize);
    }

    public function value(): Label
    {
        return $this->value;
    }

    public function caption(): Label
    {
        return $this->caption;
    }

    public function setValue(string $value): static
    {
        $this->value->setText($value);
        $this->applyIntrinsicSize();

        return $this;
    }

    public function setCaption(string $caption): static
    {
        $this->caption->setText($caption);
        $this->applyIntrinsicSize();

        return $this;
    }

    public function setValueColor(Color $color): static
    {
        $this->value->setColor($color);

        return $this;
    }

    public function setCaptionColor(Color $color): static
    {
        $this->caption->setColor($color);

        return $this;
    }

    public function setGap(int $gap): static
    {
        $this->gap = max(0, $gap);
        $this->applyIntrinsicSize();

        return $this;
    }

    public function layout(Size $available): void
    {
        $valueSize = $this->value->size();
        $captionSize = $this->caption->size();
        $gap = ($valueSize->isEmpty() || $captionSize->isEmpty()) ? 0 : $this->gap;
        $width = max($valueSize->width, $captionSize->width, $this->rect->width, $available->width);

        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $this->setSize(
                max($valueSize->width, $captionSize->width),
                $valueSize->height + $gap + $captionSize->height,
            );
            $width = $this->rect->width;
        }

        $this->value->setPosition(max(0, intdiv($width - $valueSize->width, 2)), 0);
        $this->caption->setPosition(
            max(0, intdiv($width - $captionSize->width, 2)),
            $valueSize->height + $gap,
        );
    }

    protected function draw(PaintContext $ctx): void
    {
        // Children paint themselves.
    }

    protected function applyIntrinsicSize(): void
    {
        $valueSize = $this->value->size();
        $captionSize = $this->caption->size();
        $gap = ($valueSize->isEmpty() || $captionSize->isEmpty()) ? 0 : $this->gap;

        $this->setSize(
            max($valueSize->width, $captionSize->width),
            $valueSize->height + $gap + $captionSize->height,
        );

        $this->value->setPosition(max(0, intdiv($this->rect->width - $valueSize->width, 2)), 0);
        $this->caption->setPosition(
            max(0, intdiv($this->rect->width - $captionSize->width, 2)),
            $valueSize->height + $gap,
        );
    }
}
