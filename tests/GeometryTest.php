<?php

use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Geometry\Rect;
use ScrapyardIO\UX\Geometry\Size;

test('rect intersection and translation', function () {
    $a = new Rect(0, 0, 100, 50);
    $b = new Rect(80, 25, 40, 40);

    $hit = $a->intersect($b);

    expect($hit->x)->toBe(80)
        ->and($hit->y)->toBe(25)
        ->and($hit->width)->toBe(20)
        ->and($hit->height)->toBe(25);

    $miss = $a->intersect(new Rect(200, 200, 10, 10));
    expect($miss->isEmpty())->toBeTrue();

    $moved = $a->translated(-10, -5);
    expect($moved->x)->toBe(-10)->and($moved->y)->toBe(-5);
});

test('point and size helpers', function () {
    $origin = Point::origin();
    expect($origin->equals(new Point(0, 0)))->toBeTrue();

    $size = new Size(32, 16);
    expect($size->isEmpty())->toBeFalse()
        ->and($size->atOrigin()->width)->toBe(32);
});
