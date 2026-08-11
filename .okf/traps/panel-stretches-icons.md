---
type: Trap
title: Panel stretches Icons (giant DISC)
description: Multi-child Panel must not force every child to the panel rect — Icons become full-card discs.
status: draft
---

# Panel stretches Icons (giant DISC)

## Symptom

A mint/accent filled circle covers most of a `Panel` card (menu, hints, Apply), even though `Icon::of(IconGlyph::DISC, 12, …)` was constructed.

## Cause

Older `Panel::layout` set **every** UI child to the full panel size. `Icon` draws `DISC` from its rect, so a header pip becomes a card-sized circle (`Theme` accent `#63E6C2`).

## Fix

1. `Panel` fills only a **single** child (Padding tray pattern). Multi-child panels leave child rects alone.
2. `Icon::layout` re-asserts intrinsic `extent` (and optional height) so a parent stretch cannot stick.

Prefer explicit positions on sketch stages (`ColorMenuStage`) over nested Flex when debugging cull/stretch issues.
