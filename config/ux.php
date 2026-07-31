<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Palette
    |--------------------------------------------------------------------------
    |
    | Declared once as RGB hex and packed per target from the surface's
    | FormatSpec, so the same tree reads correctly on a 1-bit OLED and an RGBA
    | window. On a monochrome panel every colour collapses to lit or unlit, so
    | keep the ink bright and the surface dark rather than relying on shades.
    |
    */

    'palette' => [
        'surface' => '#000000',
        'panel' => '#201D2B',
        'ink' => '#FFFFFF',
        'muted' => '#897F99',
        'accent' => '#63E6C2',
        'outline' => '#3A344A',
        'track' => '#09080E',
        'warning' => '#EC5D9D',
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Default extents for nodes that have an intrinsic thickness. These are sized
    | for a 128x64 panel on purpose: a value that reads well there is merely
    | small on a 1024x768 window, whereas the reverse is illegible.
    |
    */

    'metrics' => [
        'bar_thickness' => 6,
        'thumb_radius' => 4,
        'gap' => 2,
        'radius' => 0,
        'stroke' => 1,
        'pixel_radius' => 3,
        'gauge_ticks' => 5,
        'sparkline_samples' => 32,
    ],

    /*
    |--------------------------------------------------------------------------
    | Text
    |--------------------------------------------------------------------------
    |
    | A null font is the built-in classic 5x7. Named fonts resolve through the
    | font registry and fall back to classic when the registered font is a
    | data-less stub.
    |
    */

    'text' => [
        'font' => null,
        'size' => 1,
    ],

];
