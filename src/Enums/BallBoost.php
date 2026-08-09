<?php

namespace ScrapyardIO\UX\Enums;

/**
 * Click-boost tuning for {@see \ScrapyardIO\UX\Components\Ball} (tubes demo parity).
 */
enum BallBoost: string
{
    case DURATION_MS = 'DURATION_MS';
    case ACCEL_PX_S2 = 'ACCEL_PX_S2';
    case SPEED_EPS = 'SPEED_EPS';

    public function floatValue(): float
    {
        return match ($this) {
            self::DURATION_MS => 3000.0,
            self::ACCEL_PX_S2 => 3.6, // tubes MetalCanvasClickBoost::accelPerSecond()
            self::SPEED_EPS => 3.0,
        };
    }
}
