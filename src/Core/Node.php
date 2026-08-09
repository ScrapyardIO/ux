<?php

namespace ScrapyardIO\UX\Core;

/**
 * Engine-safe tree identity and lifecycle.
 *
 * No framebuffer. No Canvas. No pixels. Gameplay / folder / entity roots live here.
 */
class Node
{
    protected ?Node $parent = null;

    /**
     * @var list<Node>
     */
    protected array $children = [];

    protected string $name = '';

    protected bool $visible = true;

    protected bool $mounted = false;

    protected bool $readyCalled = false;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function parent(): ?Node
    {
        return $this->parent;
    }

    /**
     * @return list<Node>
     */
    public function children(): array
    {
        return $this->children;
    }

    public function addChild(Node $child): static
    {
        if ($child === $this) {
            throw new \InvalidArgumentException('A node cannot be its own child.');
        }

        if (! is_null($child->parent)) {
            $child->parent->removeChild($child);
        }

        $child->parent = $this;
        $this->children[] = $child;

        if ($this->mounted) {
            $child->mount();
        }

        return $this;
    }

    public function removeChild(Node $child): static
    {
        $index = array_search($child, $this->children, true);

        if ($index === false) {
            return $this;
        }

        array_splice($this->children, $index, 1);
        $child->parent = null;

        if ($child->mounted) {
            $child->unmount();
        }

        return $this;
    }

    public function clearChildren(): static
    {
        foreach ($this->children as $child) {
            $child->parent = null;

            if ($child->mounted) {
                $child->unmount();
            }
        }

        $this->children = [];

        return $this;
    }

    public function isMounted(): bool
    {
        return $this->mounted;
    }

    public function mount(): void
    {
        if ($this->mounted) {
            return;
        }

        $this->mounted = true;

        if (! $this->readyCalled) {
            $this->readyCalled = true;
            $this->ready();
        }

        foreach ($this->children as $child) {
            $child->mount();
        }
    }

    public function unmount(): void
    {
        if (! $this->mounted) {
            return;
        }

        foreach ($this->children as $child) {
            $child->unmount();
        }

        $this->mounted = false;
    }

    /**
     * Called once when first mounted into a live tree.
     */
    public function ready(): void
    {
        //
    }

    /**
     * Per-frame update. $dt is seconds since last process.
     */
    public function process(float $dt): void
    {
        if (! $this->visible) {
            return;
        }

        foreach ($this->children as $child) {
            $child->process($dt);
        }
    }

    /**
     * Depth-first walk of this subtree.
     *
     * @param  callable(Node): void  $visitor
     */
    public function walk(callable $visitor): void
    {
        $visitor($this);

        foreach ($this->children as $child) {
            $child->walk($visitor);
        }
    }
}
