<?php

namespace ScrapyardIO\UX;

use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\NutsAndBolts\AggregateServiceProvider;
use ScrapyardIO\UX\Console\ComponentMakeCommand;
use ScrapyardIO\UX\Console\UxNodeMakeCommand;
use ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\UxCanvasWindowDemo;
use ScrapyardIO\UX\Support\Theme;

/**
 * Registers UX config, generators, and the package demo sketch.
 *
 * Nodes are constructed by sketches — nothing is bound as a singleton.
 * Shared palette state is merged here and flushed so {@see Theme} reloads.
 */
class UXServiceProvider extends AggregateServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/config/ux.php',
            'ux',
        );

        parent::register();
    }

    public function boot(): void
    {
        Theme::flush();
        $this->registerSketches();

        if ($this->container->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/ux.php' => $this->container->configPath('ux.php'),
            ], 'ux-config');

            $this->container->singleton(ComponentMakeCommand::class);
            $this->container->singleton(UxNodeMakeCommand::class);

            $this->commands([
                ComponentMakeCommand::class,
                UxNodeMakeCommand::class,
            ]);
        }
    }

    protected function registerSketches(): void
    {
        $registry = $this->container->make(SketchRegistry::class);

        // Take over tubes' canvas-window-demo smoke when UX is installed.
        // Tubes registers first (package discover order); replace must not throw.
        $registry->replace(UxCanvasWindowDemo::class);

        // Explicit UX alias for docs / discovery.
        if (! $registry->has('ux-canvas-window-demo')) {
            $registry->registerConvention('ux-canvas-window-demo', UxCanvasWindowDemo::class);
        }
    }
}
