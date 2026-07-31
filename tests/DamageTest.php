<?php

namespace ScrapyardIO\UX\Tests;

use Fabricate\UX\Color;
use Fabricate\UX\Layout\Align;
use PHPUnit\Framework\TestCase;
use ScrapyardIO\UX\Chrome\Icon;
use ScrapyardIO\UX\Chrome\Panel;
use ScrapyardIO\UX\Enums\IconGlyph;
use ScrapyardIO\UX\Indicators\ProgressBar;
use ScrapyardIO\UX\Tests\Support\StageHarness;
use ScrapyardIO\UX\Text\Readout;

/**
 * The reason this library exists.
 *
 * A full SSD1306 transmit costs 20-30 ms, which caps an unconditionally redrawn
 * sketch at roughly 30 fps however little of the screen moved. So the assertions
 * here are about how much reaches the framebuffer, never about how fast painting
 * is — and they are made against a real buffer, because "what gets transmitted"
 * is a property of the buffer and not of the tree.
 */
class DamageTest extends TestCase
{
    public function testAStillTreeTransmitsNothingAtAll(): void
    {
        $harness = new StageHarness(64, 64);

        $harness->paint(Panel::of(Color::black())->add(Icon::of(IconGlyph::BOX, 8, Color::white())));

        $this->assertFalse($harness->stage->isDirty());
        $this->assertFalse($harness->stage->render());
    }

    public function testMovingANodeRepaintsWhereItWasAndWhereItWent(): void
    {
        $harness = new StageHarness(64, 64);
        $icon = Icon::of(IconGlyph::BOX, 8, Color::white());

        $harness->paint(Panel::of(Color::black())->add($icon));
        $harness->settle();

        $icon->moveTo(40, 40);
        $harness->repaint();

        $this->assertSame([[0, 0, 7, 7], [40, 40, 47, 47]], $harness->dirtyBounds());
    }

    /**
     * Erase falls out of opacity rather than out of a clear: the panel repaints
     * its own background under where the child used to be.
     */
    public function testAnOpaquePanelErasesUnderAMovedChildWithoutClearingTheSurface(): void
    {
        $harness = new StageHarness(64, 64);
        $icon = Icon::of(IconGlyph::BOX, 8, Color::white());

        $harness->paint(Panel::of(Color::black())->add($icon));
        $icon->moveTo(40, 40);
        $harness->stage->render();

        $this->assertTrue($harness->has(Color::black(), 4, 4));
        $this->assertTrue($harness->has(Color::white(), 44, 44));
        $this->assertSame(64, $harness->countOf(Color::white()));
    }

    public function testSubPixelMotionThatRoundsToTheSamePlaceIsNotDamage(): void
    {
        $harness = new StageHarness(64, 64);
        $icon = Icon::of(IconGlyph::BOX, 8, Color::white());

        $harness->paint(Panel::of(Color::black())->add($icon));

        $icon->moveTo(0, 0);

        $this->assertFalse($harness->stage->isDirty());
    }

    public function testWritingTheSameValueToAnIndicatorIsNotDamage(): void
    {
        $harness = new StageHarness(64, 16);
        $bar = ProgressBar::of(0.5);

        $harness->paint($bar);

        $bar->setValue(0.5);

        $this->assertFalse($harness->stage->isDirty());

        $bar->setValue(0.6);

        $this->assertTrue($harness->stage->isDirty());
    }

    public function testAReadoutChangingTransmitsOnlyTheReadout(): void
    {
        $harness = new StageHarness(128, 64);
        $readout = Readout::of('511', 'RAW', 2);

        $harness->paint(Panel::of(Color::black())->add(Align::centered($readout)));
        $harness->settle();

        $readout->setValue('512');
        $harness->repaint();

        $regions = $harness->dirtyBounds();
        $box = $readout->globalBounds();

        $this->assertCount(1, $regions);

        [$left, $top, $right, $bottom] = $regions[0];

        $this->assertGreaterThanOrEqual($box->x, $left);
        $this->assertGreaterThanOrEqual($box->y, $top);
        $this->assertLessThanOrEqual($box->right(), $right);
        $this->assertLessThanOrEqual($box->bottom(), $bottom);
    }

    /**
     * The rule that lets a sketch animate a colour without asking what it is
     * animating on: repainting is justified by what the surface can show, not by
     * what changed in the tree.
     *
     * A hue wash steps through hundreds of colours that a 1-bit panel renders as
     * the same unlit pixel. Treating each as damage would transmit the whole
     * frame sixty times a second to display nothing.
     */
    public function testAColourTheSurfaceCannotDistinguishIsNotDamage(): void
    {
        $harness = StageHarness::mono(128, 64);
        $panel = Panel::of(Color::black());

        $harness->paint($panel);

        foreach ([[10, 40, 70], [70, 10, 40], [40, 70, 10]] as [$r, $g, $b]) {
            $panel->setColor(Color::rgb($r, $g, $b));

            $this->assertFalse($harness->stage->isDirty(), "rgb({$r}, {$g}, {$b}) is an unlit pixel here, the same as the black it replaced.");
        }

        // The getter still answers with what it was actually given, even though
        // none of it was worth a repaint.
        $this->assertTrue($panel->color()->equals(Color::rgb(40, 70, 10)));

        // A colour the panel *can* show is damage, so this is not just an
        // invalidation that stopped working.
        $panel->setColor(Color::white());

        $this->assertTrue($harness->stage->isDirty());
    }

    public function testTheSameWashIsDamageOnASurfaceThatCanShowIt(): void
    {
        $harness = new StageHarness(128, 64);
        $panel = Panel::of(Color::black());

        $harness->paint($panel);

        $panel->setColor(Color::rgb(10, 40, 70));

        $this->assertTrue($harness->stage->isDirty());
    }

    /**
     * A paged surface cannot transmit less than a page, so damage is snapped up
     * to one — and two changes inside the same page have to become one transmit
     * rather than two.
     */
    public function testDamageOnAPagedSurfaceSnapsToWholePages(): void
    {
        $harness = StageHarness::mono(128, 64);
        $left = Icon::of(IconGlyph::BOX, 4, Color::white());
        $right = Icon::of(IconGlyph::BOX, 4, Color::white());

        $harness->paint(Panel::of(Color::black())->add($left, $right));
        $right->moveTo(60, 2);
        $harness->stage->render();
        $harness->settle();

        $left->moveTo(0, 3);
        $right->moveTo(60, 3);
        $harness->repaint();

        $regions = $harness->dirtyBounds();

        $this->assertCount(1, $regions);
        $this->assertSame([0, 0, 127, 7], $regions[0]);
    }
}
