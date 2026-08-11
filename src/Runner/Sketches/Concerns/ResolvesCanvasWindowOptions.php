<?php

namespace ScrapyardIO\UX\Runner\Sketches\Concerns;

use ScrapyardIO\Tubes\Rendering\Renderer2D;
use ScrapyardIO\Tubes\Rendering\SoftRenderer2D;

/**
 * Shared CLI parsing for UX canvas sketches.
 *
 * Expects the host Sketch to expose `$this->option()`, `$this->argument()`,
 * `$this->error()`, and a `$this->drivers` list of supported window drivers.
 *
 * Mirrors tubes CanvasWindowDemo: no driver and no --profile →
 * `tubes.defaults.canvas` (any windows.* or panels.* profile).
 */
trait ResolvesCanvasWindowOptions
{
    /**
     * No driver argument and no --profile → tubes.defaults.canvas.
     */
    protected function wantsDefaultCanvas(): bool
    {
        $driverRaw = $this->argument('driver');
        $hasDriver = is_string($driverRaw) && trim($driverRaw) !== '';

        $profileRaw = $this->option('profile');
        $hasProfile = is_string($profileRaw) && trim($profileRaw) !== '';

        return ! $hasDriver && ! $hasProfile;
    }

    /** @deprecated Use wantsDefaultCanvas() */
    protected function wantsDefaultPanelCanvas(): bool
    {
        return $this->wantsDefaultCanvas();
    }

    protected function resolveDefaultCanvasProfile(): ?string
    {
        $raw = function_exists('config') ? config('tubes.defaults.canvas') : null;
        $profile = is_string($raw) ? trim($raw) : '';

        if ($profile === '') {
            $this->error(
                'No default canvas configured. Set tubes.defaults.canvas to a canvas_profiles '
                .'windows.* or panels.* slug (e.g. st7796-front, canvas-window-demo), '
                .'or pass a window driver / --profile=….'
            );

            return null;
        }

        return $profile;
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
            return 'canvas-window-demo';
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
