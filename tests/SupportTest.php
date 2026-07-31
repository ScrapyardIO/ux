<?php

namespace ScrapyardIO\UX\Tests;

use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\UX\Color;
use PHPUnit\Framework\TestCase;
use ScrapyardIO\UX\Support\Fonts;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\Text\Label;

/**
 * A node has to be constructible with no container at all — every test in this
 * package builds a tree without booting a Machine, and so does anything that
 * composes a subtree before staging it. Both lookups below happen in
 * constructors, so neither may throw when the application is not there.
 */
class SupportTest extends TestCase
{
    protected function tearDown(): void
    {
        Theme::flush();
    }

    public function testThePaletteAnswersWithoutABootedApplication(): void
    {
        $this->assertTrue(Theme::color('ink')->equals(Color::white()));
        $this->assertTrue(Theme::color('accent')->equals(Color::fromHex('#63E6C2')));
    }

    public function testAnUnknownColourFallsBackToInkRatherThanThrowingMidFrame(): void
    {
        $this->assertTrue(Theme::color('chartreuse')->equals(Color::white()));
    }

    public function testMetricsFallBackToTheCallersDefault(): void
    {
        $this->assertSame(6, Theme::metric('bar_thickness'));
        $this->assertSame(17, Theme::metric('nonexistent', 17));
    }

    public function testOverridingThePaletteReplacesOnlyWhatItNames(): void
    {
        Theme::override(['palette' => ['ink' => '#FF0000']]);

        $this->assertTrue(Theme::color('ink')->equals(Color::fromHex('#FF0000')));
        $this->assertTrue(Theme::color('accent')->equals(Color::fromHex('#63E6C2')));

        Theme::flush();

        $this->assertTrue(Theme::color('ink')->equals(Color::white()));
    }

    public function testAMalformedHexDegradesRatherThanRefusingToPaint(): void
    {
        Theme::override(['palette' => ['ink' => 'not a colour']]);

        $this->assertTrue(Theme::color('ink')->equals(Color::white()));
    }

    public function testANodeBuiltWithNoContainerStillHasAColour(): void
    {
        $this->assertTrue(Label::of('hi')->color()->equals(Color::white()));
    }

    /**
     * Null is the renderer's own fast path for the built-in classic 5x7, so
     * "no font" and "the default font" are deliberately the same value.
     */
    public function testTheClassicFontResolvesToNull(): void
    {
        $this->assertNull(Fonts::resolve(null));
        $this->assertNull(Fonts::resolve(''));
        $this->assertNull(Fonts::resolve('classic'));
        $this->assertTrue(Fonts::isDrawable('classic'));
    }

    public function testAnUnregisteredFontFallsBackToClassicInsteadOfThrowing(): void
    {
        $this->assertNull(Fonts::resolve('a font nobody registered'));
        $this->assertFalse(Fonts::isDrawable('a font nobody registered'));
    }

    public function testThereIsAlwaysAtLeastOneDrawableFont(): void
    {
        $this->assertSame(['classic'], Fonts::drawableNames());
    }

    public function testTextStillMeasuresWhenThereIsNoRegistryToAsk(): void
    {
        $size = Label::of('hello')->setFont('a font nobody registered')->measure(Constraints::unbounded());

        $this->assertGreaterThan(0, $size->width);
    }
}
