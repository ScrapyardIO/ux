<?php

namespace ScrapyardIO\UX\Runner\Sketches\UXGuiColorMenu;

use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use ScrapyardIO\Tubes\Canvas\OSWindow;
use ScrapyardIO\Tubes\Core\MagicAliases\Window;
use ScrapyardIO\Tubes\HumanInput\EngineInput;
use ScrapyardIO\Tubes\HumanInput\Enums\MouseButton;
use ScrapyardIO\Tubes\Inputs\InputHandler;
use ScrapyardIO\Tubes\Rendering\Renderer2D;
use ScrapyardIO\Tubes\Windows\WindowException;
use ScrapyardIO\Tubes\Windows\WindowHandler;
use ScrapyardIO\UX\Core\Scene;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\GuiBackdrop;
use ScrapyardIO\UX\Runner\Sketches\Concerns\ResolvesCanvasWindowOptions;
use ScrapyardIO\UX\Runner\Sketches\UXGuiColorMenu\Assets\ColorMenuStage;
use ScrapyardIO\UX\Runner\Workflows\UxSceneFlow;
use ScrapyardIO\UX\Support\Theme;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Tutorial sketch: Menu + Button change the Scene clear colour.
 *
 *   ./runner ux-gui-color-menu
 *   ./runner ux-gui-color-menu sdl3 --fps=60
 */
#[SketchAttribute('ux-gui-color-menu')]
class UxGuiColorMenu extends Sketch
{
    use ResolvesCanvasWindowOptions;

    protected string $description = 'UX GUI tutorial — centred backdrop picker (Menu, swatch, Apply) — Ctrl-C or close to stop';

    /**
     * @var list<string>
     */
    protected array $drivers = ['metal', 'open-gl', 'vulkan', 'cuda', 'sdl3'];

    protected bool $ran = false;

    protected bool $stopRequested = false;

    protected ?Renderer2D $renderer = null;

    protected ?Scene $scene = null;

    protected ?ColorMenuStage $stage = null;

    protected ?EngineInput $engineInput = null;

    protected bool $mouseWasPressed = false;

    /**
     * @var array<string, bool>
     */
    protected array $keysWasDown = [];

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
            'Optional OS window title override',
        );

        $command->addOption(
            'width',
            'W',
            InputOption::VALUE_REQUIRED,
            'Optional window width override',
        );

        $command->addOption(
            'height',
            'H',
            InputOption::VALUE_REQUIRED,
            'Optional window height override',
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

        $title = $titleOverride ?? 'UX GUI Color Menu';
        $width = $widthOverride ?? $pending->widthValue();
        $height = $heightOverride ?? $pending->heightValue();

        $this->renderer = $this->resolveRenderer($driver);
        $this->physicsLastT = 0.0;
        $this->info("Opening UX GUI [{$profile}] → [{$driver}] {$width}x{$height} @{$fps}fps — {$title}");

        $shared = [
            'profile' => $profile,
            'fps' => $fps,
            'title' => $title,
            'should_stop' => fn (): bool => $this->stopRequested,
            'paint' => null,
        ];

        if ($driver !== $pending->driver()) {
            $shared['driver'] = $driver;
        }

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
            $this->info("UX GUI [{$openedDriver}] stopped after ".(int) ($shared['tick'] ?? 0).' ticks.');
        }

        return SketchLoopResult::STOP;
    }

    /**
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

        if (is_null($this->scene) || is_null($this->stage)) {
            $this->buildScene($window);
        }

        $stage = $this->stage;
        $scene = $this->scene;
        if (is_null($stage) || is_null($scene)) {
            return;
        }

        $this->wireInput($window, $stage);

        $scene->attach($window);
        $scene->process($dt);

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

        $initial = GuiBackdrop::MIDNIGHT;

        $this->stage = ColorMenuStage::of(
            function (GuiBackdrop $backdrop): void {
                $this->scene?->setClearColor($backdrop->color());
            },
            $initial,
        );

        $this->scene = (new Scene)
            ->attach($window)
            ->setRoot($this->stage)
            ->setClearColor($initial->color());
    }

    protected function wireInput(OSWindow $window, ColorMenuStage $stage): void
    {
        $engine = $this->engineInputFor($window);
        if (is_null($engine)) {
            return;
        }

        $this->wireMouse($engine, $stage);
        $this->wireKeyboard($engine, $stage);
    }

    protected function wireMouse(EngineInput $engine, ColorMenuStage $stage): void
    {
        $mouse = $engine->mouse();
        $pressed = ! is_null($mouse) && $mouse->isPressed(MouseButton::LEFT);
        $rising = $pressed && ! $this->mouseWasPressed;
        $this->mouseWasPressed = $pressed;

        if (! $rising || is_null($mouse)) {
            return;
        }

        $hit = $stage->hitTest((int) $mouse->x(), (int) $mouse->y());
        if (is_null($hit)) {
            return;
        }

        if ($hit === $stage->applyButton() || $this->isDescendantOf($hit, $stage->applyButton())) {
            $stage->applyButton()->activate();

            return;
        }

        $menu = $stage->menu();
        if ($hit === $menu || $this->isDescendantOf($hit, $menu)) {
            $origin = $menu->worldOrigin();
            // Select the row under the cursor and commit immediately (tutorial UX).
            $menu->selectAt((int) round($mouse->y() - $origin->y))->choose();
        }
    }

    protected function wireKeyboard(EngineInput $engine, ColorMenuStage $stage): void
    {
        $keyboard = $engine->keyboard();
        if (is_null($keyboard)) {
            return;
        }

        // Metal KeyCode::name → UP_ARROW / DOWN_ARROW / …; SDL scancode names → "up" / "down".
        $up = $this->edgeDown($this->keyDown($keyboard, 'UP_ARROW', 'Up', 'ArrowUp', 'up'), 'up');
        $down = $this->edgeDown($this->keyDown($keyboard, 'DOWN_ARROW', 'Down', 'ArrowDown', 'down'), 'down');
        $left = $this->edgeDown($this->keyDown($keyboard, 'LEFT_ARROW', 'Left', 'ArrowLeft', 'left'), 'left');
        $right = $this->edgeDown($this->keyDown($keyboard, 'RIGHT_ARROW', 'Right', 'ArrowRight', 'right'), 'right');
        $enter = $this->edgeDown(
            $this->keyDown($keyboard, 'RETURN', 'Return', 'Enter', 'return', 'SPACE', 'Space', 'space'),
            'enter',
        );
        $escape = $this->edgeDown(
            $this->keyDown($keyboard, 'ESCAPE', 'Escape', 'Esc', 'escape'),
            'escape',
        );

        if ($up || $left) {
            $stage->menu()->move(-1);
        }

        if ($down || $right) {
            $stage->menu()->move(1);
        }

        if ($enter) {
            $stage->menu()->choose();
        }

        if ($escape) {
            $this->stopRequested = true;
        }
    }

    /**
     * True if any alias is down. Case-insensitive — Metal uses UPPER enum names, SDL lower scancode names.
     */
    protected function keyDown(\ScrapyardIO\Tubes\HumanInput\Keyboard $keyboard, string ...$aliases): bool
    {
        foreach ($aliases as $alias) {
            if ($keyboard->isDown($alias)) {
                return true;
            }
        }

        $want = [];
        foreach ($aliases as $alias) {
            $want[strtolower($alias)] = true;
        }

        foreach ($keyboard->keys() as $name => $down) {
            if ($down === true && isset($want[strtolower((string) $name)])) {
                return true;
            }
        }

        return false;
    }

    protected function edgeDown(bool $down, string $key): bool
    {
        $was = $this->keysWasDown[$key] ?? false;
        $this->keysWasDown[$key] = $down;

        return $down && ! $was;
    }

    protected function isDescendantOf(UIComponent $node, UIComponent $ancestor): bool
    {
        $current = $node->parent();

        while (! is_null($current)) {
            if ($current === $ancestor) {
                return true;
            }

            $current = $current->parent();
        }

        return false;
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
