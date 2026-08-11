<?php

namespace ScrapyardIO\UX\Runner\Workflows;

use Fabricate\Sketches\Flow\Flow;
use Fabricate\Sketches\Flow\Node;
use ScrapyardIO\Tubes\Core\Runner\Sketches\Workflows\FramePaceNode;
use ScrapyardIO\Tubes\Core\Workflows\WindowLoop\CloseWindowNode;
use ScrapyardIO\Tubes\Core\Workflows\WindowLoop\OpenPanelNode;
use ScrapyardIO\Tubes\Core\Workflows\WindowLoop\OpenWindowNode;
use ScrapyardIO\Tubes\Core\Workflows\WindowLoop\PaintTickNode;

/**
 * Standard UX Scene loop: open → (paint/process via callback → present/poll → pace)* → close.
 *
 * Shared bootstrap for package sketches and app tutorials. Unlike tubes
 * {@see \ScrapyardIO\Tubes\Core\Runner\Sketches\Workflows\MetalCanvasFlow}, there is no
 * BallPhysicsNode — the paint callback owns Scene::process + paint.
 *
 * Use {@see make()} for OSWindow or {@see makePanel()} for PanelIC (tubes.defaults.canvas).
 */
class UxSceneFlow extends Flow
{
    public static function make(): self
    {
        return self::fromOpen(new OpenWindowNode);
    }

    public static function makePanel(): self
    {
        return self::fromOpen(new OpenPanelNode);
    }

    protected static function fromOpen(Node $open): self
    {
        $tick = new PaintTickNode;
        $pace = new FramePaceNode;
        $close = new CloseWindowNode;
        $failClose = new CloseWindowNode;

        $open->next($tick);
        $open->on('fail')->next($failClose);

        $tick->next($pace, 'continue');
        $tick->next($close, 'stop');
        $pace->next($tick);

        return new self($open);
    }
}
