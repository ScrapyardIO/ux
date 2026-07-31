<?php

namespace ScrapyardIO\UX\Tests;

use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use Fabricate\UX\Layout\Align;
use Fabricate\UX\Layout\Sized;
use PHPUnit\Framework\TestCase;
use ScrapyardIO\UX\Chrome\Border;
use ScrapyardIO\UX\Chrome\Icon;
use ScrapyardIO\UX\Chrome\Panel;
use ScrapyardIO\UX\Chrome\StatusBar;
use ScrapyardIO\UX\Enums\IconGlyph;
use ScrapyardIO\UX\Tests\Support\StageHarness;

class ChromeTest extends TestCase
{
    public function testAPanelCoversItsWholeBox(): void
    {
        $harness = new StageHarness(32, 16);

        $harness->paint(Panel::of(Color::white()));

        $this->assertSame(32 * 16, $harness->countOf(Color::white()));
    }

    /**
     * Opacity is a claim the stage acts on: it repaints damage starting from the
     * deepest opaque node. A node that claims it while leaving pixels alone
     * leaves ghosts, so the claim has to be exact.
     */
    public function testOnlyAPanelThatReallyCoversItsBoxClaimsToBeOpaque(): void
    {
        $this->assertTrue(Panel::of(Color::white())->isOpaque());
        $this->assertFalse(Panel::of(Color::white(), 4)->isOpaque());
        $this->assertFalse(Panel::of(Color::transparent())->isOpaque());
    }

    public function testAPanelFillsWhatItIsGivenAndShrinkWrapsWhatItIsNot(): void
    {
        $panel = Panel::of(Color::white());

        $bounded = $panel->measure(Constraints::loose(new Size(40, 20)));

        $this->assertTrue($bounded->equals(new Size(40, 20)));

        $open = Panel::of(Color::white())->add(Sized::square(6, Panel::of(Color::black())));

        $this->assertTrue($open->measure(Constraints::unbounded())->equals(new Size(6, 6)));
    }

    public function testABorderDrawsAnOutlineAndLeavesTheInsideToItsChild(): void
    {
        $harness = new StageHarness(16, 16);

        $harness->paint(new Border(Color::white(), 1, 0, Panel::of(Color::black())));

        $this->assertTrue($harness->has(Color::white(), 0, 0));
        $this->assertTrue($harness->has(Color::white(), 15, 15));
        $this->assertTrue($harness->has(Color::black(), 8, 8));
    }

    public function testABorderDeflatesTheOfferSoItsChildStillFills(): void
    {
        $harness = new StageHarness(16, 16);
        $panel = Panel::of(Color::black());

        $harness->paint(new Border(Color::white(), 2, 0, $panel));

        $this->assertTrue($panel->size()->equals(new Size(12, 12)));
        $this->assertTrue($panel->globalBounds()->contains(2, 2));
    }

    public function testASolidIconFillsItsBoxAndAHollowOneDoesNot(): void
    {
        $filled = new StageHarness(16, 16);
        $filled->paint(Align::centered(Icon::of(IconGlyph::BOX, 9, Color::white())));

        $hollow = new StageHarness(16, 16);
        $hollow->paint(Align::centered(Icon::of(IconGlyph::CIRCLE, 9, Color::white())));

        $this->assertSame(81, $filled->countOf(Color::white()));
        $this->assertLessThan(81, $hollow->countOf(Color::white()));
        $this->assertTrue(Icon::of(IconGlyph::BOX, 9, Color::white())->isOpaque());
        $this->assertFalse(Icon::of(IconGlyph::CIRCLE, 9, Color::white())->isOpaque());
    }

    public function testAnIconCanBeOblong(): void
    {
        $harness = new StageHarness(32, 16);
        $icon = Icon::of(IconGlyph::BOX, 14, Color::white())->sized(14, 10);

        $harness->paint(Align::centered($icon));

        $this->assertTrue($icon->size()->equals(new Size(14, 10)));
        $this->assertSame(140, $harness->countOf(Color::white()));
    }

    public function testAnIconScalesWithItsBoxRatherThanBeingABitmap(): void
    {
        $small = Icon::of(IconGlyph::DISC, 8)->measure(Constraints::unbounded());
        $large = Icon::of(IconGlyph::DISC, 40)->measure(Constraints::unbounded());

        $this->assertTrue($small->equals(new Size(8, 8)));
        $this->assertTrue($large->equals(new Size(40, 40)));
    }

    public function testAStatusBarPinsItsThreeRunsToTheirOwnEdges(): void
    {
        $harness = new StageHarness(128, 12);
        $bar = StatusBar::of('L', 'C', 'R');

        $harness->paint($bar);

        $left = $bar->left()->globalBounds();
        $centre = $bar->centre()->globalBounds();
        $right = $bar->right()->globalBounds();

        $this->assertLessThan($centre->x, $left->x);
        $this->assertLessThan($right->x, $centre->x);
        $this->assertGreaterThan(100, $right->x);
    }

    public function testAStatusBarWithABackgroundErasesForItself(): void
    {
        $this->assertTrue(StatusBar::of('x')->setBackground(Color::white())->isOpaque());
        $this->assertFalse(StatusBar::of('x')->setBackground(null)->isOpaque());
    }
}
