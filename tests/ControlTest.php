<?php

namespace ScrapyardIO\UX\Tests;

use Fabricate\Contracts\Actuation\HumanInput\CoordinateSpace;
use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Actuation\HumanInput\TouchPhase;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\UX\Layout\Sized;
use PHPUnit\Framework\TestCase;
use ScrapyardIO\UX\Controls\Button;
use ScrapyardIO\UX\Controls\ListView;
use ScrapyardIO\UX\Controls\Menu;
use ScrapyardIO\UX\Controls\Slider;
use ScrapyardIO\UX\Controls\Toggle;
use ScrapyardIO\UX\Tests\Support\StageHarness;

/**
 * Controls are where "the same tree on any device" is either true or not, so
 * each one is driven through all three input shapes the router can deliver:
 * a touch contact, a pointer level, and a button label.
 */
class ControlTest extends TestCase
{
    public function testAButtonFiresOnceWhenATapCompletes(): void
    {
        $presses = 0;
        $button = $this->laidOutButton($presses);

        $button->onTouch($this->contact(TouchPhase::BEGAN), new Point(4, 4));

        $this->assertTrue($button->isPressed());
        $this->assertSame(0, $presses);

        $button->onTouch($this->contact(TouchPhase::ENDED), new Point(4, 4));

        $this->assertFalse($button->isPressed());
        $this->assertSame(1, $presses);
    }

    public function testACancelledTapLeavesAButtonUnpressedAndUnfired(): void
    {
        $presses = 0;
        $button = $this->laidOutButton($presses);

        $button->onTouch($this->contact(TouchPhase::BEGAN), new Point(4, 4));
        $button->onTouch($this->contact(TouchPhase::CANCELLED), new Point(4, 4));

        $this->assertFalse($button->isPressed());
        $this->assertSame(0, $presses);
    }

    /**
     * Sliding off a button before letting go is how you change your mind, so the
     * release has to unpress it without firing it. The router keeps delivering
     * the gesture here after the finger has left, which is the only reason this
     * node ever sees a point outside itself.
     */
    public function testATapThatSlidesOffTheButtonCancelsInsteadOfFiring(): void
    {
        $presses = 0;
        $button = $this->laidOutButton($presses);

        $button->onTouch($this->contact(TouchPhase::BEGAN), new Point(4, 4));
        $button->onTouch($this->contact(TouchPhase::MOVED), new Point(400, 400));

        $this->assertTrue($button->isPressed());

        $button->onTouch($this->contact(TouchPhase::ENDED), new Point(400, 400));

        $this->assertFalse($button->isPressed());
        $this->assertSame(0, $presses);
    }

    public function testAPointerReleasedOffTheButtonCancelsInsteadOfFiring(): void
    {
        $presses = 0;
        $button = $this->laidOutButton($presses);

        $button->onPointer(new Point(4, 4), true);
        $button->onPointer(new Point(400, 400), false);

        $this->assertFalse($button->isPressed());
        $this->assertSame(0, $presses);
    }

    /**
     * A pointer reports its buttons as a level, not an edge, so a held mouse
     * would otherwise fire once per frame.
     */
    public function testAHeldPointerDoesNotRepeatOnAButton(): void
    {
        $presses = 0;
        $button = $this->laidOutButton($presses);

        for ($frame = 0; $frame < 10; $frame++) {
            $button->onPointer(new Point(4, 4), true);
        }

        $this->assertSame(0, $presses);

        $button->onPointer(new Point(4, 4), false);

        $this->assertSame(1, $presses);
    }

    public function testAHeldPointerDoesNotRepeatOnAToggle(): void
    {
        $toggle = Toggle::of(false);

        for ($frame = 0; $frame < 10; $frame++) {
            $toggle->onPointer(new Point(2, 2), true);
        }

        $this->assertTrue($toggle->isOn());

        $toggle->onPointer(new Point(2, 2), false);
        $toggle->onPointer(new Point(2, 2), true);

        $this->assertFalse($toggle->isOn());
    }

    public function testAToggleReportsItsChange(): void
    {
        $seen = [];
        $toggle = Toggle::of(false, function (bool $on) use (&$seen): void {
            $seen[] = $on;
        });

        $toggle->toggle()->toggle();

        // Setting the state it already holds is not a change, and should not be
        // reported as one.
        $toggle->setOn(false);

        $this->assertSame([true, false], $seen);
    }

    public function testASliderTakesItsValueFromWhereItWasTouched(): void
    {
        $harness = new StageHarness(100, 24);
        $slider = Slider::of(0.0);

        $harness->paint(Sized::width(100, $slider));

        $slider->onTouch($this->contact(TouchPhase::MOVED), new Point(50, 12));

        $this->assertEqualsWithDelta(0.5, $slider->value(), 0.05);

        $slider->onTouch($this->contact(TouchPhase::MOVED), new Point(-20, 12));

        $this->assertSame(0.0, $slider->value());

        $slider->onTouch($this->contact(TouchPhase::MOVED), new Point(400, 12));

        $this->assertSame(1.0, $slider->value());
    }

    public function testASliderStepsAndWrapsForADeviceWithOnlyButtons(): void
    {
        $slider = Slider::of(0.9)->setStep(0.2);

        $slider->onButton('activate');

        $this->assertEqualsWithDelta(0.0, $slider->value(), 0.0001);

        $slider->onButton('left');

        $this->assertEqualsWithDelta(1.0, $slider->value(), 0.0001);
    }

    public function testASliderOnlyReportsRealChanges(): void
    {
        $changes = 0;
        $slider = Slider::of(1.0)->onChange(function () use (&$changes): void {
            $changes++;
        });

        $slider->nudge(0.5);

        $this->assertSame(1.0, $slider->value());
        $this->assertSame(0, $changes);
    }

    public function testAListSelectsTheRowThatWasTapped(): void
    {
        $harness = new StageHarness(64, 64);
        $list = ListView::of(['one', 'two', 'three']);

        $harness->paint($list);

        $row = intdiv($list->size()->height, 3);

        $list->onTouch($this->contact(TouchPhase::ENDED), new Point(10, $row + 1));

        $this->assertSame(1, $list->selectedIndex());
        $this->assertSame('two', $list->selected());
    }

    public function testAListScrollsToKeepItsSelectionVisible(): void
    {
        $harness = new StageHarness(64, 24);
        $list = ListView::of(['a', 'b', 'c', 'd', 'e', 'f']);

        $harness->paint(Sized::height(24, $list));

        $this->assertSame(0, $list->offset());

        $list->select(5);
        $harness->stage->render();

        $this->assertSame(5, $list->selectedIndex());
        $this->assertSame(6 - $list->visibleRows(), $list->offset());
    }

    public function testAListWrapsRatherThanStickingAtTheEnds(): void
    {
        $list = ListView::of(['a', 'b', 'c']);

        $list->move(-1);

        $this->assertSame(2, $list->selectedIndex());

        $list->move(1);

        $this->assertSame(0, $list->selectedIndex());
    }

    /**
     * The difference between a list and a menu: a list moves its selection, and
     * a menu also has a moment where the selection is committed.
     */
    public function testAMenuCommitsOnTheActivationButtonAndNotOnMovement(): void
    {
        $chosen = [];
        $menu = Menu::of(['start', 'stop'])->onChoose(function (string $item, int $index) use (&$chosen): void {
            $chosen[] = "{$index}:{$item}";
        });

        $menu->select(1);

        $this->assertSame([], $chosen);

        $menu->onButton('activate');

        $this->assertSame(['1:stop'], $chosen);
    }

    public function testAMenuNeedsASecondTapOnTheSameRowToCommitIt(): void
    {
        $harness = new StageHarness(64, 64);
        $chosen = [];
        $menu = Menu::of(['start', 'stop'])->onChoose(function (string $item) use (&$chosen): void {
            $chosen[] = $item;
        });

        $harness->paint($menu);

        $row = intdiv($menu->size()->height, 2);

        $menu->onTouch($this->contact(TouchPhase::ENDED), new Point(10, $row + 1));

        $this->assertSame(1, $menu->selectedIndex());
        $this->assertSame([], $chosen);

        $menu->onTouch($this->contact(TouchPhase::ENDED), new Point(10, $row + 1));

        $this->assertSame(['stop'], $chosen);
    }

    protected function contact(TouchPhase $phase): TouchContact
    {
        return new TouchContact('finger', 0.0, 0.0, $phase, CoordinateSpace::PIXELS);
    }

    /**
     * A button with real bounds, because "is this point still on me?" is not a
     * question an unmeasured node can answer.
     */
    protected function laidOutButton(int &$presses): Button
    {
        $button = Button::of('GO', function () use (&$presses): void {
            $presses++;
        });

        (new StageHarness(64, 32))->paint(new Sized(40, 16, $button));

        return $button;
    }
}
