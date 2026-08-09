<?php

namespace ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\Assets;

use Fabricate\Sketches\Flow\Flow;
use ScrapyardIO\Tubes\Core\Runner\Sketches\Workflows\FramePaceNode;
use ScrapyardIO\Tubes\Core\Workflows\WindowLoop\CloseWindowNode;
use ScrapyardIO\Tubes\Core\Workflows\WindowLoop\OpenWindowNode;
use ScrapyardIO\Tubes\Core\Workflows\WindowLoop\PaintTickNode;

/**
 * UX Scene loop: open → (paint/process via callback → present/poll → pace)* → close.
 *
 * Unlike tubes {@see \ScrapyardIO\Tubes\Core\Runner\Sketches\Workflows\MetalCanvasFlow},
 * there is no BallPhysicsNode — the paint callback owns Scene::process + paint.
 */
class UxSceneFlow extends Flow
{
    public static function make(): self
    {
        $open = new OpenWindowNode;
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
