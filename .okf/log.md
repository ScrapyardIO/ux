# OKF change log — scrapyard-io/ux

## 2026-08-11

- **Convention**: `composer.json` `require` switched from kitchen-sink umbrellas to granular `tubes/*` + `fabricate/*`. **Retracted** `suggest scrapyard-io/tubes` workaround — UX must not import `Tubes\Core`; move seams instead. Amended [dependency-direction](conventions/dependency-direction.md), root `AGENTS.md`.
- **Fix**: UxCanvasWindowDemo HUD ~10Hz visibility throttle applies **only** when framebuffer damage is not whole-surface (PanelIC dirty/pixel). On OSWindow / whole-surface clears, hiding HUD every other frame flickered text out. Amended [ux-canvas-window-demo](core/ux-canvas-window-demo.md), [scene-pipeline](core/scene-pipeline.md).

## 2026-08-10

- **Fix**: UxCanvasWindowDemo FPS uses PaintTickNode `work_ns` (paint+present). HUD subtree visible only on 10Hz sync (not every frame). Arena chrome transparent for panel SPI. Amended [scene-pipeline](core/scene-pipeline.md).
- **Fix**: Scene clear respects framebuffer `DamageGranularity` — dirty/pixel buffers clear once; Ball erases previous splat. DemoHud must **not** wipe its full band (SPI coalesce with ball killed FPS on FT232H); Labels erase their own glyph boxes. Amended [scene-pipeline](core/scene-pipeline.md).
- **Fix**: `UxCanvasWindowDemo` no longer defaults `--profile=canvas-window-demo` (that blocked panels). No-arg → `tubes.defaults.canvas` via `CanvasProfiles::locate` + `UxSceneFlow::makePanel()` for panels. Paint uses `Canvas`. Amended [ux-canvas-window-demo](core/ux-canvas-window-demo.md).

## 2026-08-09

- Trap: multi-child `Panel` must not stretch Icons — giant accent `DISC` over GUI card; `Icon::layout` clamps extent; see `traps/panel-stretches-icons.md`.
- UxCanvasWindowDemo re-done as Scene-first showcase: `DemoStage` / `DemoHud` / `Arena` / physics `Ball`; `UxSceneFlow` (no tubes `MetalCanvasFlow` / `BallPhysicsNode`).
- Sketch + assets colocated under `Runner/Sketches/UXCanvasWindow/` (`Assets/` for DemoHud, DemoStage, UxSceneFlow).
- DemoHud perf: transparent StatusBar/Panel, narrow ProgressBar, string sync always ~10Hz (wide CPU fills were pinning ~27fps).
- Shared `Runner/Workflows/UxSceneFlow` + `ResolvesCanvasWindowOptions`; GUI tutorial sketch `ux-gui-color-menu` (`UXGuiColorMenu` + `ColorMenuStage` + `GuiBackdrop`).
- Website ecosystem Tutorials: Building Sketches / Hello World / Motion / GUI / Human Input.
- Bootstrap OKF for 0.7 reconstituting UX package.
- Documented orientation: package, ownership vs tubes, layered tree, engine extension seam.
- Documented core: provider, config, scene pipeline, generators, demo sketch, component catalog.
- Documented conventions (companion + dependency direction) and traps (no node cache, no world-sized buffers).
- Paint walk: Drawable/Scene recurse through non-drawing Node folders; ScrollView clips via `PaintContext::withClip`.
- UX replaces tubes `canvas-window-demo` via `SketchRegistry::replace()` when installed; alias `ux-canvas-window-demo`.
- Perf: Scene layout dirty-flag; cached world origins; demo drops full-window Panel after clear; HUD ~10Hz. Trap `fullscreen-panel-after-clear`.
- Status of new concepts: `draft` pending human verification.
