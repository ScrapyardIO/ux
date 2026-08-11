<?php

namespace ScrapyardIO\UX\Components\Controls;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Scrolling list of strings with one highlighted row (rows are drawn, not children).
 */
class ListView extends UIComponent
{
    /**
     * @var array<int, string>
     */
    protected array $items = [];

    protected int $selected = 0;

    protected int $offset = 0;

    protected Color $ink;

    protected Color $highlight;

    protected int $padding;

    protected int $textSize;

    protected ?string $font;

    protected bool $focused = false;

    /**
     * @param  array<int, string>  $items
     */
    public function __construct(array $items = [])
    {
        parent::__construct();

        $this->items = array_values($items);
        $this->ink = Theme::color('ink');
        $this->highlight = Theme::color('accent');
        $this->padding = Theme::metric('gap', 2);
        $this->textSize = Theme::textSize();
        $this->font = Theme::font();
        $this->applyIntrinsicSize();
    }

    /**
     * @param  array<int, string>  $items
     */
    public static function of(array $items): static
    {
        return new static($items);
    }

    /**
     * @return array<int, string>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @param  array<int, string>  $items
     */
    public function setItems(array $items): static
    {
        $this->items = array_values($items);
        $this->selected = min($this->selected, max(0, count($this->items) - 1));
        $this->applyIntrinsicSize();
        $this->followSelection();

        return $this;
    }

    public function selectedIndex(): int
    {
        return $this->selected;
    }

    public function selected(): ?string
    {
        return $this->items[$this->selected] ?? null;
    }

    public function select(int $index): static
    {
        if ($this->items === []) {
            return $this;
        }

        $this->selected = max(0, min(count($this->items) - 1, $index));
        $this->followSelection();

        return $this;
    }

    public function move(int $step): static
    {
        $count = count($this->items);

        if ($count === 0) {
            return $this;
        }

        return $this->select((($this->selected + $step) % $count + $count) % $count);
    }

    public function setHighlight(Color $highlight): static
    {
        $this->highlight = $highlight;

        return $this;
    }

    public function setTextSize(int $size): static
    {
        $this->textSize = max(1, $size);
        $this->applyIntrinsicSize();
        $this->followSelection();

        return $this;
    }

    public function visibleRows(): int
    {
        $row = $this->rowHeight();

        return ($row === 0) ? 0 : max(1, intdiv(max(1, $this->rect->height), $row));
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function setFocused(bool $focused): static
    {
        $this->focused = $focused;

        return $this;
    }

    public function isFocused(): bool
    {
        return $this->focused;
    }

    /**
     * Select the row under a local y coordinate.
     */
    public function selectAt(int $localY): static
    {
        $row = $this->rowHeight();

        if ($row === 0) {
            return $this;
        }

        return $this->select($this->offset + intdiv($localY, $row));
    }

    protected function draw(PaintContext $ctx): void
    {
        $row = $this->rowHeight();

        if ($row === 0 || $this->rect->isEmpty()) {
            return;
        }

        $rows = max(1, intdiv($this->rect->height, $row));
        $highlight = $this->highlight->pack();
        $ink = $this->ink->pack();
        $selectedInk = Theme::color('surface')->pack();

        for ($line = 0; $line < $rows; $line++) {
            $index = $this->offset + $line;

            if (! array_key_exists($index, $this->items)) {
                break;
            }

            $y = $line * $row;
            $chosen = $index === $this->selected;

            if ($chosen) {
                $ctx->fillRectLocal(0, $y, $this->rect->width, $row, $highlight);
            }

            $ctx->printLocal(
                $this->padding,
                $y + $this->padding,
                $this->items[$index],
                $chosen ? $selectedInk : $ink,
                null,
                $this->textSize,
                $this->font,
            );
        }

        if ($this->focused) {
            $ctx->drawRectLocal(0, 0, $this->rect->width, $this->rect->height, $ink);
        }
    }

    protected function followSelection(): void
    {
        $rows = $this->visibleRows();
        $count = count($this->items);

        if ($rows === 0 || $count === 0) {
            $this->offset = 0;

            return;
        }

        $offset = $this->offset;

        if ($this->selected < $offset) {
            $offset = $this->selected;
        } elseif ($this->selected >= ($offset + $rows)) {
            $offset = $this->selected - $rows + 1;
        }

        $this->offset = max(0, min($offset, max(0, $count - $rows)));
    }

    protected function rowHeight(): int
    {
        return (8 * $this->textSize) + (2 * $this->padding);
    }

    protected function applyIntrinsicSize(): void
    {
        $widest = 0;

        foreach ($this->items as $item) {
            $widest = max($widest, 6 * $this->textSize * strlen($item));
        }

        $row = $this->rowHeight();
        $this->setSize($widest + (2 * $this->padding), max($row, $row * count($this->items)));
    }
}
