<?php

namespace ScrapyardIO\UX\Core;

use ScrapyardIO\Tubes\Canvas\Canvas;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Framebuffer as FramebufferContract;
use ScrapyardIO\Tubes\Rendering\Renderer2D;
use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Presentation root: binds a tubes Canvas, owns Viewport/scroll, orchestrates layout+paint.
 *
 * Callers bind the framebuffer on the Renderer2D before {@see paint()}.
 * Engine packages may subclass Scene later.
 *
 * Clear policy follows the bound framebuffer's {@see DamageGranularity}:
 * whole-surface buffers clear every paint; pixel/dirty buffers clear once, then
 * rely on partial writes (so flush emits PARTIAL dumps, not FULL every frame).
 */
class Scene
{
    protected ?Canvas $canvas = null;

    protected ?Node $root = null;

    protected Viewport $viewport;

    protected Color $clearColor;

    protected bool $needsLayout = true;

    protected int $layoutWidth = 0;

    protected int $layoutHeight = 0;

    protected bool $surfacePrimed = false;

    public function __construct(?Viewport $viewport = null)
    {
        $this->viewport = $viewport ?? new Viewport;
        $this->clearColor = Theme::color('surface');
    }

    public function attach(Canvas $canvas): static
    {
        if ($this->canvas !== $canvas) {
            $this->canvas = $canvas;
            $this->needsLayout = true;
            $this->surfacePrimed = false;
        }

        $width = $canvas->width();
        $height = $canvas->height();

        if ($this->viewport->size()->width !== $width || $this->viewport->size()->height !== $height) {
            $this->viewport->setSize(new Size($width, $height));
            $this->needsLayout = true;
        }

        return $this;
    }

    public function canvas(): ?Canvas
    {
        return $this->canvas;
    }

    public function setRoot(Node $root): static
    {
        if (! is_null($this->root) && $this->root->isMounted()) {
            $this->root->unmount();
        }

        $this->root = $root;
        $root->mount();
        $this->needsLayout = true;

        return $this;
    }

    public function root(): ?Node
    {
        return $this->root;
    }

    public function viewport(): Viewport
    {
        return $this->viewport;
    }

    public function setScroll(int $x, int $y): static
    {
        $this->viewport->setScroll(new Point($x, $y));

        return $this;
    }

    public function setClearColor(Color $color): static
    {
        $this->clearColor = $color;

        return $this;
    }

    public function markNeedsLayout(): static
    {
        $this->needsLayout = true;

        return $this;
    }

    public function needsLayout(): bool
    {
        return $this->needsLayout;
    }

    public function process(float $dt): void
    {
        $this->root?->process($dt);
    }

    /**
     * Layout UI roots against the viewport size (when dirty), then paint drawables.
     *
     * Caller must {@see Renderer2D::setFramebuffer()} before this call.
     *
     * Prefer Scene clear for the backdrop — do not also paint a full-window opaque
     * Panel on Metal/CPU segment paths (that doubles a whole-surface write).
     */
    public function paint(Renderer2D $renderer, bool $clear = true): void
    {
        if (is_null($this->canvas) || is_null($this->root)) {
            return;
        }

        $width = $this->canvas->width();
        $height = $this->canvas->height();

        if ($this->viewport->size()->width !== $width || $this->viewport->size()->height !== $height) {
            $this->viewport->setSize(new Size($width, $height));
            $this->needsLayout = true;
        }

        if ($this->root instanceof UIComponent) {
            if ($this->needsLayout
                || $this->layoutWidth !== $width
                || $this->layoutHeight !== $height
            ) {
                $this->root->setSize($width, $height);
                $this->root->layout($this->viewport->size());
                $this->layoutWidth = $width;
                $this->layoutHeight = $height;
                $this->needsLayout = false;
            }
        }

        if ($clear && $this->shouldClearSurface($renderer->framebuffer())) {
            $renderer->fill($this->clearColor->pack());
            $this->surfacePrimed = true;
        }

        $ctx = new PaintContext(
            $renderer,
            $this->viewport->scroll(),
            $this->viewport->clipRect(),
        );

        $this->paintNode($this->root, $ctx);
    }

    /**
     * Whole-surface damage → clear every frame.
     * Pixel / dirty damage → clear once (prime), then partial paints only.
     */
    protected function shouldClearSurface(FramebufferContract $framebuffer): bool
    {
        $granularity = $framebuffer->damageGranularity();

        if ($granularity->coversWholeSurface()) {
            return true;
        }

        return ! $this->surfacePrimed;
    }

    /**
     * Paint a Drawable root, or walk non-drawing Node folders for Drawable descendants.
     */
    protected function paintNode(Node $node, PaintContext $ctx): void
    {
        if (! $node->isVisible()) {
            return;
        }

        if ($node instanceof Drawable) {
            $node->paint($ctx);

            return;
        }

        foreach ($node->children() as $child) {
            $this->paintNode($child, $ctx);
        }
    }
}
