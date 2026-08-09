<?php

namespace ScrapyardIO\UX\Components;

use ScrapyardIO\UX\Core\Node;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Geometry\Size;

/**
 * Viewport over a larger content area.
 *
 * Child rects are stored in content space; layout places them at content − scroll.
 */
class ScrollView extends UIComponent
{
    protected Size $contentSize;

    protected Point $scroll;

    /**
     * Content-space origins keyed by spl_object_id.
     *
     * @var array<int, Point>
     */
    protected array $contentOrigins = [];

    public function __construct(?Size $contentSize = null, ?Size $viewportSize = null)
    {
        parent::__construct();

        $this->contentSize = $contentSize ?? Size::zero();
        $this->scroll = Point::origin();

        if (! is_null($viewportSize)) {
            $this->setSize($viewportSize->width, $viewportSize->height);
        }
    }

    public static function of(int $viewportWidth, int $viewportHeight, ?Size $contentSize = null): static
    {
        return new static($contentSize, new Size($viewportWidth, $viewportHeight));
    }

    public function contentSize(): Size
    {
        return $this->contentSize;
    }

    public function setContentSize(Size $size): static
    {
        $this->contentSize = $size;
        $this->clampScroll();

        return $this;
    }

    public function scroll(): Point
    {
        return $this->scroll;
    }

    public function setScroll(int $x, int $y): static
    {
        $this->scroll = new Point($x, $y);
        $this->clampScroll();
        $this->layout($this->size());

        return $this;
    }

    public function scrollBy(int $dx, int $dy): static
    {
        return $this->setScroll($this->scroll->x + $dx, $this->scroll->y + $dy);
    }

    /**
     * Place (or move) a child in content coordinates.
     */
    public function placeChild(UIComponent $child, int $x, int $y): static
    {
        if (! in_array($child, $this->children, true)) {
            $this->addChild($child);
        }

        $this->contentOrigins[spl_object_id($child)] = new Point($x, $y);
        $child->setPosition($x - $this->scroll->x, $y - $this->scroll->y);

        return $this;
    }

    public function addChild(Node $child): static
    {
        parent::addChild($child);

        if ($child instanceof UIComponent) {
            $id = spl_object_id($child);

            if (! isset($this->contentOrigins[$id])) {
                $this->contentOrigins[$id] = new Point($child->rect()->x, $child->rect()->y);
            }
        }

        return $this;
    }

    public function removeChild(Node $child): static
    {
        unset($this->contentOrigins[spl_object_id($child)]);

        return parent::removeChild($child);
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $this->clampScroll();

        $maxW = 0;
        $maxH = 0;

        foreach ($this->children as $child) {
            if (! $child instanceof UIComponent || ! $child->isVisible()) {
                continue;
            }

            $id = spl_object_id($child);

            if (! isset($this->contentOrigins[$id])) {
                $this->contentOrigins[$id] = new Point($child->rect()->x, $child->rect()->y);
            }

            $origin = $this->contentOrigins[$id];
            $child->setPosition($origin->x - $this->scroll->x, $origin->y - $this->scroll->y);
            $child->layout($child->size()->isEmpty() ? $available : $child->size());

            $size = $child->size();
            $maxW = max($maxW, $origin->x + $size->width);
            $maxH = max($maxH, $origin->y + $size->height);
        }

        if ($this->contentSize->isEmpty() && ($maxW > 0 || $maxH > 0)) {
            $this->contentSize = new Size($maxW, $maxH);
            $this->clampScroll();
        }
    }

    public function paint(PaintContext $ctx): void
    {
        if (! $this->visible) {
            return;
        }

        $world = $this->worldRect();

        if (is_null($world) || $ctx->cullRect($world)->isEmpty()) {
            return;
        }

        $viewportClip = $ctx->cullRect($world);
        $clipped = $ctx->withClip($viewportClip);
        $childCtx = $clipped->withOrigin($world->origin());

        $this->draw($childCtx);
        $this->paintDescendants($this, $clipped);
    }

    protected function draw(PaintContext $ctx): void
    {
        // Viewport chrome is optional; children paint at scrolled positions inside clip.
    }

    protected function clampScroll(): void
    {
        $maxX = max(0, $this->contentSize->width - $this->rect->width);
        $maxY = max(0, $this->contentSize->height - $this->rect->height);

        $this->scroll = new Point(
            max(0, min($this->scroll->x, $maxX)),
            max(0, min($this->scroll->y, $maxY)),
        );
    }
}
