<?php

use ScrapyardIO\UX\Core\Node;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

test('node child graph add and remove', function () {
    $root = new Node('root');
    $child = new Node('child');

    $root->addChild($child);

    expect($root->children())->toHaveCount(1)
        ->and($child->parent())->toBe($root);

    $root->removeChild($child);

    expect($root->children())->toBeEmpty()
        ->and($child->parent())->toBeNull();
});

test('mount calls ready once and process skips invisible', function () {
    $readyCount = 0;
    $processCount = 0;

    $root = new class($readyCount, $processCount) extends Node
    {
        public function __construct(private int &$readyCount, private int &$processCount)
        {
            parent::__construct('counter');
        }

        public function ready(): void
        {
            $this->readyCount++;
        }

        public function process(float $dt): void
        {
            if (! $this->isVisible()) {
                return;
            }

            $this->processCount++;
            parent::process($dt);
        }
    };

    $root->mount();
    $root->mount();
    expect($readyCount)->toBe(1);

    $root->process(0.016);
    expect($processCount)->toBe(1);

    $root->setVisible(false);
    $root->process(0.016);
    expect($processCount)->toBe(1);
});

test('node can hold non-drawing children beside UIComponent', function () {
    $folder = new Node('entities');
    $ui = new class extends UIComponent
    {
        protected function draw(PaintContext $ctx): void {}
    };
    $ui->setRect(new Rect(0, 0, 10, 10));

    $folder->addChild(new Node('npc'));
    $folder->addChild($ui);

    expect($folder->children())->toHaveCount(2)
        ->and($folder->children()[0])->toBeInstanceOf(Node::class)
        ->and($folder->children()[1])->toBeInstanceOf(UIComponent::class);
});
