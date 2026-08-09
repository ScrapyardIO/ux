<?php

use ScrapyardIO\Tubes\Rendering\SoftRenderer2D;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Geometry\Rect;

test('paint context culls world rects outside scroll clip', function () {
    $ctx = new PaintContext(
        new SoftRenderer2D,
        new Point(100, 50),
        new Rect(0, 0, 200, 100),
    );

    $visible = $ctx->cullRect(new Rect(120, 60, 40, 20));
    expect($visible->isEmpty())->toBeFalse()
        ->and($visible->x)->toBe(20)
        ->and($visible->y)->toBe(10)
        ->and($visible->width)->toBe(40)
        ->and($visible->height)->toBe(20);

    $offscreen = $ctx->cullRect(new Rect(0, 0, 10, 10));
    expect($offscreen->isEmpty())->toBeTrue();
});
