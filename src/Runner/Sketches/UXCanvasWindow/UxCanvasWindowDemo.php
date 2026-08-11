<?php

namespace ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow;

use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use ScrapyardIO\Tubes\Canvas\Canvas;
use ScrapyardIO\Tubes\Canvas\OSWindow;
use ScrapyardIO\Tubes\Canvas\PanelIC;
use ScrapyardIO\Tubes\Core\Enums\CanvasProfileKind;
use ScrapyardIO\Tubes\Core\MagicAliases\Panel;
use ScrapyardIO\Tubes\Core\MagicAliases\Window;
use ScrapyardIO\Tubes\Core\Support\CanvasProfiles;
use ScrapyardIO\Tubes\HumanInput\EngineInput;
use ScrapyardIO\Tubes\HumanInput\Enums\MouseButton;
use ScrapyardIO\Tubes\Inputs\InputHandler;
use ScrapyardIO\Tubes\Panels\PanelException;
use ScrapyardIO\Tubes\Rendering\Renderer2D;
use ScrapyardIO\Tubes\Windows\WindowException;
use ScrapyardIO\Tubes\Windows\WindowHandler;
use ScrapyardIO\UX\Core\Scene;
use ScrapyardIO\UX\Runner\Sketches\Concerns\ResolvesCanvasWindowOptions;
use ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\Assets\DemoStage;
use ScrapyardIO\UX\Runner\Workflows\UxSceneFlow;
use ScrapyardIO\UX\Support\Theme;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * UX Scene showcase for the tubes canvas-window-demo slot.
 *
 * Replaces tubes CanvasWindowDemo when UX is installed.
 * No CLI driver / --profile → tubes.defaults.canvas (windows.* or panels.*).
 *
 *   ./runner canvas-window-demo
 *   ./runner canvas-window-demo sdl3 --fps=60
 *   ./runner ux-canvas-window-demo
 */
#[SketchAttribute('canvas-window-demo')]
class UxCanvasWindowDemo extends Sketch
{
    use ResolvesCanvasWindowOptions;

    protected string $description = 'UX Scene showcase — DemoHud + Arena/Ball — default tubes.defaults.canvas (panel or window); Ctrl-C to stop';

    /**
     * @var list<string>
     */
    protected array $drivers = ['metal', 'open-gl', 'vulkan', 'cuda', 'sdl3'];

    protected bool $ran = false;

    protected bool $stopRequested = false;

    protected ?Renderer2D $renderer = null;

    protected ?Scene $scene = null;

    protected ?DemoStage $stage = null;

    protected ?EngineInput $engineInput = null;

    protected float $measuredFps = 0.0;

    protected ?int $lastPaintNs = null;

    protected ?int $lastHudNs = null;

    protected bool $hudPrimed = false;

    protected float $physicsLastT = 0.0;

    public function configureCommand(Command $command): void
    {
        $command->addArgument(
            'driver',
            InputArgument::OPTIONAL,
            'Optional window driver override (metal|open-gl|vulkan|cuda|sdl3). Omitting driver+profile uses tubes.defaults.canvas.',
        );

        // No default — a default here would block tubes.defaults.canvas forever.
        $command->addOption(
            'profile',
            null,
            InputOption::VALUE_REQUIRED,
            'Window profile under tubes.canvas_profiles.windows (forces window path when set)',
        );

        $command->addOption(
            'title',
            null,
            InputOption::VALUE_REQUIRED,
            'Optional OS window title override (default: profile title)',
        );

        $command->addOption(
            'width',
            'W',
            InputOption::VALUE_REQUIRED,
            'Optional window width override (default: profile width)',
        );

        $command->addOption(
            'height',
            'H',
            InputOption::VALUE_REQUIRED,
            'Optional window height override (default: profile height)',
        );

        $command->addOption(
            'fps',
            null,
            InputOption::VALUE_REQUIRED,
            'Target frames per second',
            '60',
        );
    }

    public function boot(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        $requestStop = function (): void {
            $this->stopRequested = true;
        };

        pcntl_signal(SIGINT, $requestStop);
        pcntl_signal(SIGTERM, $requestStop);
    }

    public function loop(): SketchLoopResult
    {
        if ($this->ran) {
            return SketchLoopResult::STOP;
        }

        $this->ran = true;

        if ($this->wantsDefaultCanvas()) {
            return $this->runDefaultCanvas();
        }

        return $this->runWindowCanvas($this->resolveProfile());
    }

    protected function runDefaultCanvas(): SketchLoopResult
    {
        $slug = $this->resolveDefaultCanvasProfile();
        if (is_null($slug)) {
            return SketchLoopResult::STOP;
        }

        try {
            [$kind] = CanvasProfiles::locate($slug);
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return SketchLoopResult::STOP;
        }

        return match ($kind) {
            CanvasProfileKind::PANELS => $this->runPanelCanvas($slug),
            CanvasProfileKind::WINDOWS => $this->runWindowCanvas($slug),
        };
    }

    protected function runPanelCanvas(string $panelProfile): SketchLoopResult
    {
        $fps = $this->resolvePositiveInt('fps', 60);
        if (is_null($fps)) {
            return SketchLoopResult::STOP;
        }

        try {
            $panel = Panel::profile($panelProfile);
        } catch (PanelException $exception) {
            $this->error($exception->getMessage());

            return SketchLoopResult::STOP;
        }

        $this->renderer = $panel->renderer();
        $width = $panel->width();
        $height = $panel->height();
        $this->measuredFps = (float) $fps;
        $this->lastPaintNs = null;
        $this->lastHudNs = null;
        $this->hudPrimed = false;
        $this->physicsLastT = 0.0;

        $this->info(
            "Opening UX Scene panel [{$panelProfile}] {$width}x{$height} @{$fps}fps via ".$this->renderer::class
        );

        $shared = [
            'canvas' => $panel,
            'panel_profile' => $panelProfile,
            'width' => $width,
            'height' => $height,
            'fps' => $fps,
            'should_stop' => fn (): bool => $this->stopRequested,
            'paint' => function (Canvas $canvas, int $tick) use (&$shared): void {
                $this->frame($canvas, $tick, $shared);
            },
        ];

        UxSceneFlow::makePanel()->run($shared);

        $this->teardown();

        if (isset($shared['error']) && is_string($shared['error'])) {
            $this->error($shared['error']);
        } else {
            $this->info("UX Scene panel [{$panelProfile}] stopped after ".(int) ($shared['tick'] ?? 0).' ticks.');
        }

        return SketchLoopResult::STOP;
    }

    protected function runWindowCanvas(?string $profile): SketchLoopResult
    {
        if (is_null($profile) || $profile === '') {
            $profile = 'canvas-window-demo';
        }

        try {
            $pending = Window::profile($profile);
        } catch (WindowException $exception) {
            $this->error($exception->getMessage());

            return SketchLoopResult::STOP;
        }

        $driver = $this->resolveDriverOverride() ?? $pending->driver();
        if (is_null($this->assertDriverSupported($driver))) {
            return SketchLoopResult::STOP;
        }

        $titleOverride = $this->resolveOptionalString('title');
        $widthOverride = $this->resolveOptionalPositiveInt('width');
        $heightOverride = $this->resolveOptionalPositiveInt('height');
        $fps = $this->resolvePositiveInt('fps', 60);

        if ($widthOverride === false || $heightOverride === false || is_null($fps)) {
            return SketchLoopResult::STOP;
        }

        $title = $titleOverride ?? ($pending->titleValue().' (UX)');
        $width = $widthOverride ?? $pending->widthValue();
        $height = $heightOverride ?? $pending->heightValue();

        $this->renderer = $this->resolveRenderer($driver);
        $this->measuredFps = (float) $fps;
        $this->lastPaintNs = null;
        $this->lastHudNs = null;
        $this->hudPrimed = false;
        $this->physicsLastT = 0.0;
        $this->info("Opening UX Scene demo [{$profile}] → [{$driver}] {$width}x{$height} @{$fps}fps — {$title}");

        $shared = [
            'profile' => $profile,
            'fps' => $fps,
            'should_stop' => fn (): bool => $this->stopRequested,
            'paint' => null,
        ];

        if ($driver !== $pending->driver()) {
            $shared['driver'] = $driver;
        }

        $shared['title'] = $title;

        if (! is_null($widthOverride)) {
            $shared['width'] = $widthOverride;
        }

        if (! is_null($heightOverride)) {
            $shared['height'] = $heightOverride;
        }

        $shared['paint'] = function (Canvas $canvas, int $tick) use (&$shared): void {
            $this->frame($canvas, $tick, $shared);
        };

        UxSceneFlow::make()->run($shared);

        $this->teardown();

        if (isset($shared['error']) && is_string($shared['error'])) {
            $this->error($shared['error']);
        } else {
            $openedDriver = is_string($shared['driver'] ?? null) ? $shared['driver'] : $driver;
            $this->info("UX Scene [{$openedDriver}] stopped after ".(int) ($shared['tick'] ?? 0).' ticks.');
        }

        return SketchLoopResult::STOP;
    }

    protected function teardown(): void
    {
        $this->renderer?->unsetFramebuffer();
        $this->renderer = null;
        $this->scene = null;
        $this->stage = null;
        $this->engineInput = null;
    }

    /**
     * One frame: pace stamp → input → Scene::process → HUD sync → Scene::paint.
     *
     * @param  array<string, mixed>  $shared
     */
    protected function frame(Canvas $canvas, int $tick, array &$shared): void
    {
        $renderer = $this->renderer;
        if (is_null($renderer)) {
            return;
        }

        $shared['frame_t0'] = hrtime(true);
        $nowNs = $shared['frame_t0'];
        $dt = $this->resolveDeltaSeconds($nowNs, is_int($shared['fps'] ?? null) ? $shared['fps'] : 60);
        $shared['dt'] = $dt;

        // Prefer last tick's paint+present wall time (set by PaintTickNode). Inter-paint
        // cadence includes FramePace sleep and lied at ~30 while SPI looked like ~3.
        $workNs = $shared['work_ns'] ?? null;
        if (is_int($workNs) && $workNs > 0) {
            $instant = 1_000_000_000.0 / $workNs;
            $this->measuredFps = ($this->measuredFps * 0.9) + ($instant * 0.1);
        } elseif (! is_null($this->lastPaintNs)) {
            $elapsed = $nowNs - $this->lastPaintNs;
            if ($elapsed > 0) {
                $instant = 1_000_000_000.0 / $elapsed;
                $this->measuredFps = ($this->measuredFps * 0.9) + ($instant * 0.1);
            }
        }
        $this->lastPaintNs = $nowNs;

        if (is_null($this->scene) || is_null($this->stage)) {
            $this->buildScene($canvas);
        }

        $stage = $this->stage;
        $scene = $this->scene;
        if (is_null($stage) || is_null($scene)) {
            return;
        }

        if ($canvas instanceof OSWindow) {
            $this->wirePointer($canvas, $stage);
        }

        $scene->attach($canvas);
        $scene->process($dt);

        // HUD ~10Hz only on dirty/partial PanelIC (SPI digit churn). OSWindow /
        // whole-surface FBs clear every paint — hiding HUD there flickers text out.
        $throttleHud = ! $canvas->framebuffer()->damageGranularity()->coversWholeSurface();
        $hudDue = is_null($this->lastHudNs) || ($nowNs - $this->lastHudNs) >= 100_000_000;
        $paintHud = (! $throttleHud) || $hudDue || ! $this->hudPrimed;

        if ($paintHud) {
            $this->lastHudNs = $nowNs;
            $this->hudPrimed = true;
            if ($stage->sync($this->measuredFps, $tick)) {
                $scene->markNeedsLayout();
            }
            $stage->hud()->setVisible(true);
        } elseif ($throttleHud) {
            // Dirty PanelIC: skip HUD subtree SPI — only Ball erase/redraw each frame.
            $stage->hud()->setVisible(false);
        }

        $fb = $canvas->framebuffer();
        $renderer->setFramebuffer($fb);

        try {
            $scene->paint($renderer);
        } finally {
            if (! $canvas instanceof PanelIC) {
                $renderer->unsetFramebuffer();
            }
        }
    }

    protected function buildScene(Canvas $canvas): void
    {
        Theme::flush();

        $this->stage = DemoStage::of(0.85, 24);
        $this->scene = (new Scene)
            ->attach($canvas)
            ->setRoot($this->stage)
            ->setClearColor(Theme::color('surface'));
    }

    protected function wirePointer(OSWindow $window, DemoStage $stage): void
    {
        $engine = $this->engineInputFor($window);
        if (is_null($engine)) {
            return;
        }

        $mouse = $engine->mouse();
        $pressed = ! is_null($mouse) && $mouse->isPressed(MouseButton::LEFT);

        if (is_null($mouse)) {
            $stage->ball()->handlePointer(false, 0.0, 0.0);

            return;
        }

        $origin = $stage->arena()->worldOrigin();
        $stage->ball()->handlePointer(
            $pressed,
            (float) $mouse->x() - $origin->x,
            (float) $mouse->y() - $origin->y,
        );
    }

    protected function engineInputFor(OSWindow $window): ?EngineInput
    {
        if (! is_null($this->engineInput)) {
            return $this->engineInput;
        }

        $handler = $window->handler();
        if (! $handler instanceof WindowHandler || ! method_exists($handler, 'inputHandler')) {
            return null;
        }

        $input = $handler->inputHandler();
        if (! $input instanceof InputHandler) {
            return null;
        }

        return $this->engineInput = new EngineInput($input);
    }

    protected function resolveDeltaSeconds(int $nowNs, int $fps): float
    {
        $fallback = 1.0 / max(1, $fps);

        if ($this->physicsLastT <= 0.0) {
            $this->physicsLastT = $nowNs;

            return $fallback;
        }

        $dt = ($nowNs - $this->physicsLastT) / 1_000_000_000.0;
        $this->physicsLastT = $nowNs;

        return max(1.0 / 240.0, min(0.05, $dt));
    }
}
