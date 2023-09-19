<?php

namespace Hybridly\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\suggest;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'make:table')]
class MakeTableCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    protected $name = 'make:table';
    protected $description = 'Create a new table class';
    protected $type = 'Table';

    protected function getStub()
    {
        $stub = match (true) {
            !empty($this->option('model')) => '/stubs/table.model.php.stub',
            default => '/stubs/table.php.stub',
        };

        return $this->resolveStubPath($stub);
    }

    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . '/../..' . $stub;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Tables';
    }

    protected function buildClass($name)
    {
        $tableNamespace = $this->getNamespace($name);

        $replace = [];

        if ($this->option('model')) {
            $replace = $this->buildModelReplacements($replace);
        }

        $replace["use {$tableNamespace};\n"] = '';

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name),
        );
    }

    protected function buildModelReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));

        if (!class_exists($modelClass) && confirm("A {$modelClass} model does not exist. Do you want to generate it?", default: true)) {
            $this->call('make:model', ['name' => $modelClass]);
        }

        return array_merge($replace, [
            '{{ namespacedModel }}' => $modelClass,
            '{{ model }}' => class_basename($modelClass),
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
        ]);
    }

    protected function parseModel($table)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $table)) {
            throw new InvalidArgumentException('Table name contains invalid characters.');
        }

        return $this->qualifyModel($table);
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if (!$this->hasOption('model') || !empty($this->option('model'))) {
            return;
        }

        $model = suggest(
            label: 'What model should this table be for? (optional)',
            options: $this->possibleModels(),
        );

        if ($model) {
            $input->setOption('model', $model);
        }
    }

    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the table already exists'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a table for the given model'],
        ];
    }
}
