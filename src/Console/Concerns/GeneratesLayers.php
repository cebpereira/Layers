<?php

declare(strict_types=1);

namespace CebPereira\Layers\Console\Concerns;

use CebPereira\Layers\Support\LayerTarget;
use CebPereira\Layers\Support\Paths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait GeneratesLayers
{
    /**
     * Write a layer file from a stub, skipping files that already exist.
     *
     * @param  array<string, string>  $replacements
     */
    protected function writeLayer(string $type, LayerTarget $target, string $stub, array $replacements): bool
    {
        $path = Paths::relative(base_path(), $target->path) ?? $target->path;

        if (File::exists($target->path)) {
            $this->components->error(sprintf('%s [%s] already exists.', $type, $path));

            return false;
        }

        File::ensureDirectoryExists(dirname($target->path));
        File::put($target->path, $this->renderStub($stub, [
            'namespace' => $target->namespace,
            'class' => $target->class,
        ] + $replacements));

        $this->components->info(sprintf('%s [%s] created successfully.', $type, $path));

        return true;
    }

    /**
     * Stub contents with placeholders replaced. Lines holding an empty placeholder are removed.
     *
     * @param  array<string, string>  $replacements
     */
    protected function renderStub(string $stub, array $replacements): string
    {
        $contents = File::get($this->stubPath($stub));

        foreach ($replacements as $key => $value) {
            if ($value === '') {
                $contents = (string) preg_replace(
                    '/^[ \t]*\{\{ ?' . preg_quote($key, '/') . ' ?\}\}\R(?:[ \t]*\R)?/m',
                    '',
                    $contents
                );
            }

            $contents = str_replace(['{{ ' . $key . ' }}', '{{' . $key . '}}'], $value, $contents);
        }

        return $contents;
    }

    /**
     * Published stub (stubs/layers) or the package default.
     */
    protected function stubPath(string $stub): string
    {
        $published = base_path('stubs/layers/' . $stub . '.stub');

        return File::exists($published) ? $published : __DIR__ . '/../Commands/Stubs/' . $stub . '.stub';
    }

    /**
     * "use" statements for classes outside the given namespace.
     *
     * @param  array<int, string>  $classes
     */
    protected function imports(string $namespace, array $classes): string
    {
        return collect($classes)
            ->unique()
            ->reject(fn (string $class): bool => Str::beforeLast($class, '\\') === $namespace)
            ->sort()
            ->map(fn (string $class): string => "use {$class};")
            ->implode("\n");
    }
}
