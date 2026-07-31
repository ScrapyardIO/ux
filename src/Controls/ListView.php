<?php

namespace ScrapyardIO\UX\Controls;

use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Actuation\HumanInput\TouchPhase;
use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\Contracts\UX\InputTarget;
use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use Fabricate\UX\Concerns\ReceivesInput;
use ScrapyardIO\UX\Concerns\DrawsText;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A scrolling list of strings with one row highlighted.
 *
 * The rows are drawn rather than being child nodes. A list of thirty items would
 * otherwise be thirty nodes to measure and place every time the selection moved,
 * for rows that are all the same height and none of which the caller ever
 * addresses individually — the tree would be paying for structure nobody uses.
 *
 * Scrolling follows the selection rather than being set: the window shifts by the
 * least amount that brings the selected row back into view, which is what keeps a
 * four-row panel usable with only two buttons.
 */
class ListView extends UXNode implements InputTarget
{
    use DrawsText;
    use ReceivesInput;

    /**
     * @var array<int, string>
     */
    protected array $items = [];

    protected int $selected = 0;

    protected int $offset = 0;

    protected Color $highlight;

    protected int $padding;

    /**
     * @param  array<int, string>  $items
     */
    public function __construct(array $items = [])
    {
        parent::__construct();

        $this->initialiseText('');

        $this->items = array_values($items);
        $this->highlight = Theme::color('accent');
        $this->padding = Theme::metric('gap', 2);
        $this->text_alignment = Alignment::centerLeft();
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
        $this->metrics = null;

        return $this->markNeedsLayout()->invalidate();
    }

    public function selectedIndex(): int
    {
        return $this->selected;
    }

    public function selected(): ?string
    {
        return $this->items[$this->selected] ?? null;
    }

    /**
     * Moving the selection is paint damage: the list is the same size, a
     * different row is lit.
     */
    public function select(int $index): static
    {
        if ($this->items === []) {
            return $this;
        }

        $index = max(0, min(count($this->items) - 1, $index));

        if ($this->selected === $index) {
            return $this;
        }

        $this->selected = $index;

        $this->followSelection();

        return $this->invalidate();
    }

    /**
     * Wraps at both ends, matching how focus traversal behaves, so a list and the
     * ring around it do not disagree about what "past the end" means.
     */
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
        if ($this->highlight->equals($highlight)) {
            return $this;
        }

        $this->highlight = $highlight;

        return $this->invalidate();
    }

    /**
     * How many rows fit in the current box.
     */
    public function visibleRows(): int
    {
        $row = $this->rowHeight();

        return ($row === 0) ? 0 : max(1, intdiv($this->bounds()->height, $row));
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function measure(Constraints $constraints): Size
    {
        $row = $this->rowHeight();
        $natural = max($row, $row * count($this->items));

        $size = $this->spanning($constraints, Axis::VERTICAL, $natural, $this->widestRow());

        // The window depends on how many rows fit, which is only known once the
        // box is, so the scroll position is reconciled at the end of layout.
        $this->followSelection();

        return $size;
    }

    public function paint(DrawingSurface $surface): void
    {
        $box = $this->localBounds();
        $row = $this->rowHeight();

        if (($row === 0) || $box->isEmpty()) {
            return;
        }

        $rows = max(1, intdiv($box->height, $row));
        $highlight = $this->packed($this->highlight);
        $ink = $this->packed($this->ink);

        for ($line = 0; $line < $rows; $line++) {
            $index = $this->offset + $line;

            if (! array_key_exists($index, $this->items)) {
                break;
            }

            $y = $line * $row;
            $chosen = $index === $this->selected;

            if ($chosen) {
                $surface->fillRect(0, $y, $box->width, $row, $highlight);
            }

            $this->paintRunOfText(
                $surface,
                $this->items[$index],
                new Rect($this->padding, $y, max(0, $box->width - (2 * $this->padding)), $row),
                Alignment::centerLeft(),
                $chosen ? $this->highlightedInk() : null,
            );
        }

        // Not part of the rows, so a list that scrolls does not have its focus
        // ring scroll away with the selection.
        if ($this->focused) {
            $surface->drawRect(0, 0, $box->width, $box->height, $ink);
        }
    }

    public function onTouch(TouchContact $contact, Point $local): bool
    {
        if ($contact->phase !== TouchPhase::ENDED) {
            return true;
        }

        return $this->selectAt($local);
    }

    public function onPointer(Point $local, bool $pressed): bool
    {
        return $pressed && $this->selectAt($local);
    }

    public function onButton(string $label): bool
    {
        $this->move(1);

        return true;
    }

    /**
     * The selected row is drawn in the surface colour so it reads against the
     * highlight, which on a monochrome panel is the only way to stay legible —
     * there is no darker shade of lit.
     */
    protected function highlightedInk(): Color
    {
        return Theme::color('surface');
    }

    protected function selectAt(Point $local): bool
    {
        $row = $this->rowHeight();

        if (($row === 0) || ! $this->covers($local)) {
            return false;
        }

        $this->select($this->offset + intdiv($local->y, $row));

        return true;
    }

    /**
     * Shift the window by the smallest amount that brings the selection back
     * into view, and never past the end of the list.
     */
    protected function followSelection(): void
    {
        $rows = $this->visibleRows();
        $count = count($this->items);

        if (($rows === 0) || ($count === 0)) {
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

    /**
     * Measured from a glyph rather than from an item, so an empty list still has
     * a row height and a list of blank strings does not collapse.
     *
     * Deliberately not cached: the font and text size can both change under it,
     * and two characters of getTextBounds costs far less than a stale row height
     * that silently misplaces every row.
     */
    protected function rowHeight(): int
    {
        return $this->measureText('Ag', $this->applied_text_size)->size->height + (2 * $this->padding);
    }

    protected function widestRow(): int
    {
        $widest = 0;

        foreach ($this->items as $item) {
            $widest = max($widest, $this->measureText($item, $this->applied_text_size)->size->width);
        }

        return $widest + (2 * $this->padding);
    }
}
