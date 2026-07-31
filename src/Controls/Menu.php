<?php

namespace ScrapyardIO\UX\Controls;

use Closure;
use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Actuation\HumanInput\TouchPhase;
use Fabricate\NutsAndBolts\Geometry\Point;

/**
 * A {@see ListView} whose rows can be chosen, not merely highlighted.
 *
 * The distinction is the whole class. A list moves its selection; a menu also has
 * a moment where the selection is *committed*, and separating the two is what
 * lets a d-pad browse without firing anything until the action button is pressed.
 *
 * Choosing is reported by name as well as by index, because a menu's callers
 * think in labels while its state is an offset, and making every handler
 * translate between the two is how off-by-one bugs get in.
 */
class Menu extends ListView
{
    /**
     * @var Closure(string, int, static): void|null
     */
    protected ?Closure $on_choose = null;

    /**
     * @param  Closure(string, int, static): void  $handler
     */
    public function onChoose(Closure $handler): static
    {
        $this->on_choose = $handler;

        return $this;
    }

    /**
     * Commit whatever is currently selected. Safe on an empty menu, which is the
     * state a menu built from a device scan is in until the scan finishes.
     */
    public function choose(): static
    {
        $item = $this->selected();

        if (is_null($item) || is_null($this->on_choose)) {
            return $this;
        }

        ($this->on_choose)($item, $this->selectedIndex(), $this);

        return $this;
    }

    /**
     * The activation button commits rather than advancing the selection, which
     * is where a menu and a plain list part company.
     */
    public function onButton(string $label): bool
    {
        $this->choose();

        return true;
    }

    /**
     * A tap on the row that is already selected commits it; a tap elsewhere only
     * moves the selection. That is the standard two-step every touch UI uses to
     * stop a stray contact from firing an action.
     */
    public function onTouch(TouchContact $contact, Point $local): bool
    {
        if ($contact->phase !== TouchPhase::ENDED) {
            return true;
        }

        $before = $this->selectedIndex();

        parent::onTouch($contact, $local);

        if ($this->selectedIndex() === $before) {
            $this->choose();
        }

        return true;
    }
}
