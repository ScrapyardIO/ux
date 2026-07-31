<?php

namespace ScrapyardIO\UX;

use Fabricate\Core\Machine as ScrapyardIOMachine;
use Fabricate\NutsAndBolts\AggregateServiceProvider;
use ScrapyardIO\UX\Support\Theme;

/**
 * Registers the node library's configuration.
 *
 * There is nothing to bind: a node is constructed by the sketch that composes it,
 * not resolved from the container. The one piece of shared state is the palette,
 * which is merged here and cached by {@see Theme} on first read.
 */
class UXServiceProvider extends AggregateServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->publishConfig();
    }

    public function boot(): void
    {
        // The config is merged during register(), so anything Theme cached while
        // the container was still being assembled is now stale.
        Theme::flush();
    }

    protected function publishConfig(): void
    {
        $source = realpath($raw = __DIR__.'/../config/ux.php') ?: $raw;

        if ($this->program instanceof ScrapyardIOMachine && $this->program->runningInConsole()) {
            $this->publishes([$source => $this->program->configPath('ux.php')]);
        }

        $this->mergeConfigFrom($source, 'ux');
    }
}
