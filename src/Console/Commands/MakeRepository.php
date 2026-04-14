<?php

declare(strict_types=1);

namespace CebPereira\Layers\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MakeRepository extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        layers:repository {name}
        {--e|eloquent : Generate a repository eloquent for the model}
        {--i|interface : Generate a repository interface for the model}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a repository file';

    protected $type = 'Repository file';

    protected function getNameInput(): string
    {
        return str_replace('.', '/', trim($this->argument('name')));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        $stubs_path = base_path('vendor/cebpereira/layers') . '/src/Console/Commands/Stubs/';
        return $stubs_path . 'Repository' . $this->getType() . '.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\' . config('layers.namespace.repositories');
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . 'Repository' . $this->getType() . '.php';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        return $this->replaceModel($stub, $name);
    }

    /**
     * Replace the model for the given stub.
     *
     * @param  string  $stub
     * @param  string  $model
     * @return string
     */
    protected function replaceModel($stub, $model): string
    {
        $modelClass = $this->parseModel($model);

        $replace = [
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $stub
        );
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel(string $model): string
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

    /**
     * Get the repository type.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getType(): string
    {
        $options = $this->options();

        if ($options['eloquent'] && $options['interface']) {
            throw new InvalidArgumentException('More than one option provided: expected \'eloquent\' or \'interface\', not both.');
        } elseif ($options['eloquent']) {
            return 'Eloquent';
        } elseif ($options['interface']) {
            return 'Interface';
        }

        throw new InvalidArgumentException('Invalid option: expected \'eloquent\' or \'interface\'.');
    }
}
