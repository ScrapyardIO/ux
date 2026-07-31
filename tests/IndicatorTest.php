<?php

namespace ScrapyardIO\UX\Tests;

use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\UX\Color;
use Fabricate\UX\Layout\Align;
use PHPUnit\Framework\TestCase;
use ScrapyardIO\UX\Indicators\Gauge;
use ScrapyardIO\UX\Indicators\PixelStrip;
use ScrapyardIO\UX\Indicators\ProgressBar;
use ScrapyardIO\UX\Indicators\Sparkline;
use ScrapyardIO\UX\Tests\Support\StageHarness;

class IndicatorTest extends TestCase
{
    public function testAProgressBarFillsInProportionToItsValue(): void
    {
        $harness = new StageHarness(100, 8);

        $harness->paint(ProgressBar::of(0.5)->setColors(Color::white(), Color::transparent()));

        $fill = $harness->boundsOf(Color::white());

        $this->assertNotNull($fill);
        $this->assertSame(0, $fill->x);
        $this->assertSame(50, $fill->width);
        $this->assertSame(8, $fill->height);
    }

    public function testAnEmptyProgressBarPaintsNoFillAtAll(): void
    {
        $harness = new StageHarness(100, 8);

        $harness->paint(ProgressBar::of(0.0)->setColors(Color::white(), Color::transparent()));

        $this->assertNull($harness->boundsOf(Color::white()));
    }

    /**
     * Every normalised input in this library comes from an ADC, a ratio or a
     * caller, and none of the three can be trusted to stay in range.
     */
    public function testAProgressBarClampsWhatItIsGiven(): void
    {
        $bar = ProgressBar::of(0.0);

        $this->assertSame(1.0, $bar->setValue(4.2)->value());
        $this->assertSame(0.0, $bar->setValue(-1.0)->value());
    }

    public function testAVerticalProgressBarFillsFromTheBottom(): void
    {
        $harness = new StageHarness(8, 100);

        $harness->paint(ProgressBar::of(0.25, Axis::VERTICAL)->setColors(Color::white(), Color::transparent()));

        $fill = $harness->boundsOf(Color::white());

        $this->assertNotNull($fill);
        $this->assertSame(75, $fill->y);
        $this->assertSame(25, $fill->height);
    }

    public function testAPixelStripLightsOnlyThePixelsThatAreSet(): void
    {
        $harness = new StageHarness(64, 16);
        $strip = PixelStrip::of(4)->setCell(8, 2)->setRound(false)->setSocket(null);

        $strip->setPixel(0, Color::white());
        $strip->setPixel(1, Color::white());

        $harness->paint($strip);

        $lit = $harness->boundsOf(Color::white());

        $this->assertNotNull($lit);
        $this->assertSame(0, $lit->x);
        // Two 8px cells and the 2px gap between them, and nothing after.
        $this->assertSame(18, $lit->width);
    }

    public function testAPixelStripSizesItselfToItsCellsAndGaps(): void
    {
        $harness = new StageHarness(64, 16);
        $strip = PixelStrip::of(4)->setCell(8, 2);

        // Centred rather than staged as the root, because a root is given the
        // surface tightly and has no say in its size. Offered room, the strip
        // takes only what its cells need.
        $harness->paint(Align::centered($strip));

        // Four cells, three gaps: the node knows its own extent, so nothing
        // upstream has to compute 431 + ($pixel * 54).
        $this->assertSame((4 * 8) + (3 * 2), $strip->size()->width);
        $this->assertSame(8, $strip->size()->height);
    }

    public function testClearingAPixelStripPutsEveryPixelOut(): void
    {
        $harness = new StageHarness(64, 16);
        $strip = PixelStrip::of(4)->setCell(8, 2)->setRound(false)->setSocket(null);

        $strip->setPixels(array_fill(0, 4, Color::white()));
        $harness->paint($strip);

        $this->assertGreaterThan(0, $harness->countOf(Color::white()));

        $strip->clear();
        $harness->stage->render();

        $this->assertSame(0, $harness->countOf(Color::white()));
    }

    public function testASparklineDrawsHighSamplesAboveLowOnes(): void
    {
        $harness = new StageHarness(32, 16);
        $line = Sparkline::of(4, Color::white());

        $line->push(0.0)->push(1.0);

        $harness->paint($line);

        $trace = $harness->boundsOf(Color::white());

        $this->assertNotNull($trace);
        $this->assertSame(0, $trace->y);
        $this->assertGreaterThan(1, $trace->height);
    }

    public function testASparklineKeepsOnlyItsMostRecentSamples(): void
    {
        $line = Sparkline::of(3);

        $line->push(1.0)->push(2.0)->push(3.0)->push(4.0);

        $this->assertSame([2.0, 3.0, 4.0], $line->samples());
    }

    public function testAGaugeNeedleMovesWithItsValue(): void
    {
        $low = new StageHarness(32, 32);
        $high = new StageHarness(32, 32);

        $low->paint(Gauge::of(0.0, 31)->setColors(Color::white(), Color::transparent()));
        $high->paint(Gauge::of(1.0, 31)->setColors(Color::white(), Color::transparent()));

        $left = $low->boundsOf(Color::white());
        $right = $high->boundsOf(Color::white());

        $this->assertNotNull($left);
        $this->assertNotNull($right);
        $this->assertLessThan($right->x, $left->x);
    }
}
