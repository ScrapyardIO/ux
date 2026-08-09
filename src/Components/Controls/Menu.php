<?php

namespace ScrapyardIO\UX\Components\Controls;

use Closure;

/**
 * A {@see ListView} whose rows can be committed (chosen), not merely highlighted.
 */
class Menu extends ListView
{
    /**
     * @var Closure(string, int, static): void|null
     */
    protected ?Closure $onChoose = null;

    /**
     * @param  Closure(string, int, static): void  $handler
     */
    public function onChoose(Closure $handler): static
    {
        $this->onChoose = $handler;

        return $this;
    }

    public function choose(): static
    {
        $item = $this->selected();

        if (is_null($item) || is_null($this->onChoose)) {
            return $this;
        }

        ($this->onChoose)($item, $this->selectedIndex(), $this);

        return $this;
    }

    /**
     * Tap helper: selecting the already-selected row commits it.
     */
    public function activateAt(int $localY): static
    {
        $before = $this->selectedIndex();
        $this->selectAt($localY);

        if ($this->selectedIndex() === $before) {
            $this->choose();
        }

        return $this;
    }
}
