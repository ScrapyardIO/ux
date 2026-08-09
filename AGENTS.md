# Agent guidelines — scrapyard-io/ux

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing UX code or advising on ScrapyardIO scene / node / widget architecture **for this package**:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only. New/changed concepts stay `status: draft` until a human verifies them.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`.
5. Keep the `.okf` bundle at the **package root** only — do not nest extra `.okf` folders under `src/`.
6. Tubes display / framebuffer / Renderer2D knowledge belongs in `scrapyard-io/tubes` `.okf`, not here. Framework core knowledge belongs in `scrapyard-io/framework`.

## Package rules (quick) — 0.7.x

- Composer: `scrapyard-io/ux` **0.7.0**. PHP `^8.4|^8.5|^8.6`. Namespace `ScrapyardIO\UX\` → `src/`.
- Discovery: `extra.scrapyard-io.providers` → `ScrapyardIO\UX\UXServiceProvider`.
- Requires `scrapyard-io/tubes ^0.7` + `scrapyard-io/framework ^0.7` — **not** `fabricate/ux ^0.6`.
- **Ownership:** Canvas (tubes) owns the framebuffer; `Renderer2D` borrows it; UX nodes never own pixel buffers on the default path.
- **Layered tree (engine-ready):** `Node` (lifecycle) → `Drawable` (paint) → `UIComponent` (rect/layout/hit). A future game-engine package may depend on / extend `Node` / `Drawable` / `Scene` without UI-only baggage.
- **Present:** only `Scene` binds a tubes `Canvas`. Do not put Window/Canvas types on `Node`.
- **Layout (UI layer):** parent-relative rects + child injection on `UIComponent`. No Flutter Constraints on the Node base.
- **World vs viewport:** virtual world coords + Scene camera/scroll; cull/clip on paint; never allocate a world-sized buffer.
- Generators: `make:component` → `UIComponent` stub; `make:ux-node` → `Node` stub (disambiguates Flow’s `make:node`).
- Demo sketch: `Runner/Sketches/UXCanvasWindow/UxCanvasWindowDemo` **replaces** tubes `canvas-window-demo` via `SketchRegistry::replace()` when UX is installed; alias `ux-canvas-window-demo`. Sketch assets in `UXCanvasWindow/Assets/` (`DemoStage`, `DemoHud`, `UxSceneFlow`). Catalog `Arena` / physics `Ball` via `Scene::process` — not tubes `MetalCanvasFlow` / `BallPhysicsNode`.
- `_src/` and 0.6 Fabricate UX are **donors only** — not the runtime tree.
