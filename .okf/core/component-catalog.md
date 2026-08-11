---
okf_version: "0.2"
status: draft
id: component-catalog
title: Component catalog (0.7)
---

# Component catalog

Ported and new `UIComponent` widgets under `ScrapyardIO\UX\Components\`. All paint via `PaintContext`; no Fabricate Constraints / DrawingSurface / Node APIs.

## Text

| Class | Role |
|-------|------|
| `Text\Label` | Intrinsic 6×8 text run |
| `Text\Readout` | Value + caption Labels stacked |
| `Text\Marquee` | Scrolling overflow text (`offset` / `advance` / `isScrolling`) |

## Chrome

| Class | Role |
|-------|------|
| `Chrome\Panel` | Filled / rounded backdrop (fills **one** UI child; multi-child keeps own rects) |
| `Chrome\StatusBar` | Left / centre / right Labels |
| `Chrome\Border` | Outline around optional child |
| `Chrome\Icon` | `IconGlyph` primitives; layout clamps to `extent` (never parent-stretch) |

Sketch-local composition (`DemoHud`, `DemoStage`, `ColorMenuStage`, …) lives under `Runner/Sketches/*/Assets/`. Shared loop: `Runner/Workflows/UxSceneFlow`.

## Indicators

| Class | Role |
|-------|------|
| `Indicators\ProgressBar` | Normalised fill on Axis |
| `Indicators\Gauge` | Tick arc + needle |
| `Indicators\Sparkline` | Rolling polyline samples |
| `Indicators\PixelStrip` | LED-style cell strip |

## Controls (visual + state; input phase later)

| Class | Role |
|-------|------|
| `Controls\Button` | pressed/focused + `onPress` / `activate` |
| `Controls\Toggle` | Boolean track+knob |
| `Controls\Slider` | 0..1 track+thumb (`seekTo` ready) |
| `Controls\ListView` | String rows + selection scroll |
| `Controls\Menu` | ListView + `choose` / `activateAt` |
| `Controls\Checkbox` | Checked square |
| `Controls\Radio` | Grouped disc selection |

## Layout

| Class | Role |
|-------|------|
| `Layout\Flex` / `Row` / `Column` | Axis line + gap; expands `Expanded`/`Spacer` |
| `Layout\Stack` | Same-origin overlay |
| `Layout\Padding` | Edge insets |
| `Layout\Sized` | Fixed width/height wrapper |
| `Layout\Align` | 0..1 x/y child alignment |
| `Layout\Spacer` | Flex empty space |
| `Layout\SizedBox` | Explicit empty box |
| `Layout\Positioned` | Absolute x/y wrapper |
| `Layout\Expanded` | Flex grow child |

## Other

| Class | Role |
|-------|------|
| `Ball` | Filled circle + optional Arena physics (velocity, bounce, click boost) |
| `Arena` | Bounded playfield (outline; restitution for Ball) |
| `ScrollView` | Content size + local scroll offset on children |
| `Support\Fonts` | `resolve(?string): ?string` classic passthrough |
