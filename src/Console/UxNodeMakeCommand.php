<?php

namespace ScrapyardIO\UX\Console;

use Fabricate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * UX tree Node stub. Named make:ux-node so it does not collide with Flow's make:node.
 */
#[AsCommand(name: 'make:ux-node')]
class UxNodeMakeCommand extends GeneratorCommand
{
    protected string $name = 'make:ux-node';

    protected string $description = 'Create a new UX Node class (lifecycle only; no paint/layout)';

    /**
     * @var string
     */
    protected $type = 'UX Node';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/node.stub');
    }

    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->scrapyard_io->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Nodes';
    }

    /**
     * @return array<int, array{0: string, 1: string|null, 2: int, 3?: string}>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the node already exists'],
        ];
    }
}
