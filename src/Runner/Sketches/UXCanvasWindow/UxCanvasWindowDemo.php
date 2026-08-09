<?php

namespace ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow;

use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use ScrapyardIO\Tubes\Canvas\OSWindow;
use ScrapyardIO\Tubes\Core\MagicAliases\Window;
use ScrapyardIO\Tubes\HumanInput\EngineInput;
use ScrapyardIO\Tubes\HumanInput\Enums\MouseButton;
use ScrapyardIO\Tubes\Inputs\InputHandler;
use ScrapyardIO\Tubes\Rendering\Renderer2D;
use ScrapyardIO\Tubes\Rendering\SoftRenderer2D;
use ScrapyardIO\Tubes\Windows\WindowException;
use ScrapyardIO\Tubes\Windows\WindowHandler;
use ScrapyardIO\UX\Core\Scene;
use ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\Assets\DemoStage;
use ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\Assets\UxSceneFlow;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * UX Scene showcase for the tubes canvas-window-demo slot.
 *
 * When scrapyard-io/ux is installed, UXServiceProvider replaces tubes'
 * CanvasWindowDemo binding so `./runner canvas-window-demo` runs this sketch.
 * Alias: `ux-canvas-window-demo`.
 *
 * Tubes owns Window / Renderer2D / present / poll. UX owns the scene tree:
 * DemoStage → DemoHud (StatusBar, Border, Panel, Flex, ProgressBar, Readout, Icon)
 * + Arena → Ball (integrate / bounce / click boost via Scene::process).
 *
 *   ./runner canvas-window-demo
 *   ./runner canvas-window-demo sdl3 --fps=60
 *   ./runner ux-canvas-window-demo
 */
#[SketchAttribute('canvas-window-demo')]
class UxCanvasWindowDemo extends Sketch
{
    protected string $description = 'UX Scene showcase — DemoHud + Arena/Ball physics (replaces tubes canvas-window-demo) — Ctrl-C or close window to stop';

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

    protected float $physicsLastT = 0.0;

    public function configureCommand(Command $command): void
    {
        $command->addArgument(
            'driver',
            InputArgument::OPTIONAL,
            'Optional window driver override (metal|open-gl|vulkan|cuda|sdl3). Default: profile driver.',
        );

        $command->addOption(
            'profile',
            null,
            InputOption::VALUE_REQUIRED,
            'Window profile under tubes.canvas_profiles.windows',
            'canvas-window-demo',
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

        $profile = $this->resolveProfile();
        if (is_null($profile)) {
            return SketchLoopResult::STOP;
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

        $shared['paint'] = function (OSWindow $window, int $tick) use (&$shared): void {
            $this->frame($window, $tick, $shared);
        };

        UxSceneFlow::make()->run($shared);

        $this->renderer?->unsetFramebuffer();
        $this->renderer = null;
        $this->scene = null;
        $this->stage = null;
        $this->engineInput = null;

        if (isset($shared['error']) && is_string($shared['error'])) {
            $this->error($shared['error']);
        } else {
            $openedDriver = is_string($shared['driver'] ?? null) ? $shared['driver'] : $driver;
            $this->info("UX Scene [{$openedDriver}] stopped after ".(int) ($shared['tick'] ?? 0).' ticks.');
        }

        return SketchLoopResult::STOP;
    }

    /**
     * One frame: pace stamp → input → Scene::process → HUD sync → Scene::paint.
     *
     * @param  array<string, mixed>  $shared
     */
    protected function frame(OSWindow $window, int $tick, array &$shared): void
    {
        $renderer = $this->renderer;
        if (is_null($renderer)) {
            return;
        }

        $shared['frame_t0'] = hrtime(true);
        $nowNs = $shared['frame_t0'];
        $dt = $this->resolveDeltaSeconds($nowNs, is_int($shared['fps'] ?? null) ? $shared['fps'] : 60);
        $shared['dt'] = $dt;

        if (! is_null($this->lastPaintNs)) {
            $elapsed = $nowNs - $this->lastPaintNs;
            if ($elapsed > 0) {
                $instant = 1_000_000_000.0 / $elapsed;
                $this->measuredFps = ($this->measuredFps * 0.9) + ($instant * 0.1);
            }
        }
        $this->lastPaintNs = $nowNs;

        if (is_null($this->scene) || is_null($this->stage)) {
            $this->buildScene($window);
        }

        $stage = $this->stage;
        $scene = $this->scene;
        if (is_null($stage) || is_null($scene)) {
            return;
        }

        $this->wirePointer($window, $stage);

        $scene->attach($window);
        $scene->process($dt);

        // Strings / layout ~10Hz. ProgressBar value can update every frame cheaply.
        // (Do not sync sprintf HUD every frame while boosting — that was a layout churn trap.)
        $hudDue = is_null($this->lastHudNs) || ($nowNs - $this->lastHudNs) >= 100_000_000;

        if ($hudDue) {
            $this->lastHudNs = $nowNs;
            if ($stage->sync($this->measuredFps, $tick)) {
                $scene->markNeedsLayout();
            }
        } else {
            $stage->hud()->syncBoostBar($stage->ball());
        }

        $fb = $window->framebuffer();
        $renderer->setFramebuffer($fb);

        try {
            $scene->paint($renderer);
        } finally {
            $renderer->unsetFramebuffer();
        }
    }

    protected function buildScene(OSWindow $window): void
    {
        Theme::flush();

        $this->stage = DemoStage::of(0.85, 24);
        $this->scene = (new Scene)
            ->attach($window)
            ->setRoot($this->stage)
            ->setClearColor(Color::fromHex('#141820'));
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

    protected function resolveRenderer(string $driver): Renderer2D
    {
        /** @var array<string, class-string<Renderer2D>> $map */
        $map = [
            'metal' => 'Microscrap\\GFX\\Metal\\MetalRenderer2D',
            'open-gl' => 'Microscrap\\GFX\\OGX\\OpenGLRenderer2D',
            'vulkan' => 'Microscrap\\GFX\\Vulkan\\VulkanRenderer2D',
            'cuda' => 'Microscrap\\GFX\\CUDA\\CudaGPURenderer2D',
            'sdl3' => 'Microscrap\\GFX\\SDL3\\SDL3Renderer2D',
        ];

        $class = $map[$driver] ?? null;
        if (! is_null($class) && class_exists($class)) {
            return new $class;
        }

        return new SoftRenderer2D;
    }

    protected function resolveProfile(): ?string
    {
        $raw = $this->option('profile');
        $profile = is_string($raw) ? trim($raw) : '';

        if ($profile === '') {
            $this->error('Option --profile must be a non-empty canvas window profile slug.');

            return null;
        }

        return $profile;
    }

    protected function resolveDriverOverride(): ?string
    {
        $raw = $this->argument('driver');
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $driver = strtolower(trim($raw));
        if ($driver === 'opengl') {
            $driver = 'open-gl';
        }

        return $this->assertDriverSupported($driver);
    }

    protected function assertDriverSupported(string $driver): ?string
    {
        if (! in_array($driver, $this->drivers, true)) {
            $this->error('Unsupported driver ['.$driver.']. Use: '.implode('|', $this->drivers));

            return null;
        }

        return $driver;
    }

    protected function resolveOptionalString(string $option): ?string
    {
        $raw = $this->option($option);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        return $raw;
    }

    /**
     * @return int|null|false null = unset, false = invalid, int = override
     */
    protected function resolveOptionalPositiveInt(string $option): int|false|null
    {
        $raw = $this->option($option);

        if (is_null($raw) || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            $this->error("Option --{$option} must be a positive integer.");

            return false;
        }

        $value = (int) $raw;

        if ($value < 1) {
            $this->error("Option --{$option} must be >= 1.");

            return false;
        }

        return $value;
    }

    protected function resolvePositiveInt(string $option, int $default): ?int
    {
        $raw = $this->option($option);

        if (is_null($raw) || $raw === '') {
            return $default;
        }

        if (! is_numeric($raw)) {
            $this->error("Option --{$option} must be a positive integer.");

            return null;
        }

        $value = (int) $raw;

        if ($value < 1) {
            $this->error("Option --{$option} must be >= 1.");

            return null;
        }

        return $value;
    }
}
