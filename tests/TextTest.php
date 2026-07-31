<?php

namespace ScrapyardIO\UX\Tests;

use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use Fabricate\UX\Layout\Align;
use Fabricate\UX\Layout\Sized;
use PHPUnit\Framework\TestCase;
use ScrapyardIO\UX\Chrome\Panel;
use ScrapyardIO\UX\Tests\Support\StageHarness;
use ScrapyardIO\UX\Text\Label;
use ScrapyardIO\UX\Text\Marquee;
use ScrapyardIO\UX\Text\Readout;

/**
 * The text nodes exist to retire one specific line of arithmetic that every
 * sketch in this repo used to open-code:
 *
 *     $x = intdiv($width - $bounds['w'], 2) - $bounds['x1'];
 *
 * So these tests are about placement and measurement, not about glyph shapes.
 */
class TextTest extends TestCase
{
    public function testALabelMeasuresItselfToItsOwnGlyphs(): void
    {
        $harness = new StageHarness(128, 64);
        $label = Label::of('Ag');

        // Wrapped, because the root of a tree is given the surface tightly and
        // has no choice about its size — intrinsic sizing is what a label does
        // when it is offered room rather than told.
        $harness->paint(Align::centered($label));

        $size = $label->size();

        $this->assertGreaterThan(0, $size->width);
        $this->assertGreaterThan(0, $size->height);

        // Two characters of a 6x8 cell, and nowhere near the 128x64 surface it
        // was offered.
        $this->assertLessThan(32, $size->width);
        $this->assertLessThan(24, $size->height);
    }

    public function testACenteredLabelLandsInTheMiddleWithoutAnyoneComputingACoordinate(): void
    {
        $harness = new StageHarness(128, 64);

        $harness->paint(Align::centered(Label::of('Ag', Color::white())));

        // The glyphs specifically, not "any written pixel": on a colour surface
        // the stage's own black background writes every pixel it erases.
        $ink = $harness->boundsOf(Color::white());

        $this->assertNotNull($ink);

        $centre_x = $ink->x + intdiv($ink->width, 2);
        $centre_y = $ink->y + intdiv($ink->height, 2);

        // Within a glyph cell of the surface centre: exactness here would be
        // asserting the font's own bearings, not the centring.
        $this->assertLessThanOrEqual(4, abs($centre_x - 64));
        $this->assertLessThanOrEqual(4, abs($centre_y - 32));
    }

    public function testAutoFitPicksTheLargestSizeThatFitsTheOffer(): void
    {
        $harness = new StageHarness(128, 64);
        $label = Label::of('ScrapyardIO')->fitTextTo(3);

        $harness->paint($label);

        // 11 characters at size 3 is 198px on a 128px panel, so the fit has to
        // come down rather than overflow and clip.
        $this->assertSame(1, $label->textSize());
        $this->assertLessThanOrEqual(128, $label->size()->width);
    }

    public function testAutoFitKeepsTheCeilingWhenThereIsRoom(): void
    {
        $harness = new StageHarness(320, 240);
        $label = Label::of('OK')->fitTextTo(3);

        $harness->paint($label);

        $this->assertSame(3, $label->textSize());
    }

    public function testAlignmentMovesTheTextInsideAStretchedBox(): void
    {
        $harness = new StageHarness(128, 64);

        $harness->paint(Sized::width(128, Label::of('Ag', Color::white())->setTextAlignment(Alignment::centerLeft())));

        $ink = $harness->boundsOf(Color::white());

        $this->assertNotNull($ink);
        $this->assertLessThanOrEqual(1, $ink->x);
    }

    public function testChangingTextToTheSameExtentDoesNotRelayout(): void
    {
        $harness = new StageHarness(128, 64);
        $label = Label::of('12');

        $harness->paint($label);

        $before = $label->bounds();

        $label->setText('34');

        $this->assertFalse($label->needsLayout());
        $this->assertTrue($label->bounds()->equals($before));
    }

    public function testChangingTextToADifferentExtentDoesRelayout(): void
    {
        $harness = new StageHarness(128, 64);
        $label = Label::of('12');

        $harness->paint($label);

        $label->setText('1234567');

        $this->assertTrue($label->needsLayout());
    }

    public function testAReadoutStacksItsValueAboveItsCaption(): void
    {
        $harness = new StageHarness(128, 64);
        $readout = Readout::of('512', 'RAW', 2);

        $harness->paint($readout);

        $value = $readout->value()->globalBounds();
        $caption = $readout->caption()->globalBounds();

        $this->assertLessThan($caption->y, $value->y);
        $this->assertGreaterThanOrEqual($value->height, $readout->size()->height - $caption->height);
    }

    public function testAReadoutOnlyRepaintsWhenItsValueMoves(): void
    {
        $harness = new StageHarness(128, 64);
        $readout = Readout::of('511', 'RAW', 2);

        $harness->paint($readout);
        $harness->settle();

        $readout->setValue('511');

        $this->assertFalse($harness->stage->isDirty());

        $readout->setValue('512');

        $this->assertTrue($harness->stage->isDirty());
    }

    public function testAMarqueeOnlyScrollsWhenTheTextOverflows(): void
    {
        $harness = new StageHarness(128, 16);
        $short = Marquee::of('hi');
        $long = Marquee::of('a headline far too long for one hundred and twenty eight pixels');

        $harness->paint(Sized::width(60, $short));
        $this->assertFalse($short->isScrolling());

        (new StageHarness(128, 16))->paint(Sized::width(60, $long));
        $this->assertTrue($long->isScrolling());
    }

    public function testAdvancingAMarqueeWrapsAtTheEndOfTheRun(): void
    {
        $harness = new StageHarness(128, 16);
        $marquee = Marquee::of('scrolling headline text')->setSeparation(8);

        $harness->paint(Sized::width(48, $marquee));

        $marquee->advance(4);
        $this->assertSame(4, $marquee->offset());

        for ($step = 0; $step < 400; $step++) {
            $marquee->advance(4);
        }

        // Wrapped rather than run away: the offset is bounded by the run plus
        // its separation no matter how long it scrolls.
        $this->assertLessThan($marquee->measure(Constraints::unbounded())->width + 64, $marquee->offset());
    }

    public function testTextPaintsAsLitPixelsOnAMonochromePanel(): void
    {
        $harness = StageHarness::mono(128, 64);

        $harness->paint(Panel::of(Color::black())->add(Align::centered(Label::of('Ag', Color::white()))));

        // The same tree, no per-depth branching, and the ink still lands: a
        // white Color resolves to a lit pixel on a 1-bit surface.
        $this->assertGreaterThan(0, $harness->litCount());
    }

    public function testALabelMeasuredWithoutAStageStillReportsASensibleSize(): void
    {
        // A subtree is routinely built before it is attached, and a zero-sized
        // label would make the containers around it lay out wrongly.
        $size = Label::of('hello')->measure(Constraints::unbounded());

        $this->assertTrue($size->equals(new Size(30, 8)));
    }
}
