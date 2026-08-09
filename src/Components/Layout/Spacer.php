<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;

/**
 * Flexible empty space that claims leftover main-axis room inside {@see Flex}.
 */
class Spacer extends UIComponent
{
    protected int $weight;

    public function __construct(int $weight = 1)
    {
        parent::__construct();

        $this->weight = max(0, $weight);
        $this->setSize(0, 0);
    }

    public static function of(int $weight = 1): static
    {
        return new static($weight);
    }

    public function weight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = max(0, $weight);

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        // Empty.
    }
}
