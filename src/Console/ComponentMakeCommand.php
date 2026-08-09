<?php

namespace ScrapyardIO\UX\Console;

use Fabricate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:component')]
class ComponentMakeCommand extends GeneratorCommand
{
    protected string $name = 'make:component';

    protected string $description = 'Create a new UX UIComponent class';

    /**
     * @var string
     */
    protected $type = 'Component';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/component.stub');
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
        return $rootNamespace.'\Components';
    }

    /**
     * @return array<int, array{0: string, 1: string|null, 2: int, 3?: string}>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the component already exists'],
        ];
    }
}
