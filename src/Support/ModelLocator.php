<?php

declare(strict_types=1);

namespace CebPereira\Layers\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

final class ModelLocator
{
    /**
     * @var array<int, ModelRoot>|null
     */
    private ?array $roots = null;

    /**
     * @var Collection<int, ModelReference>|null
     */
    private ?Collection $models = null;

    /**
     * @return array<int, ModelRoot>
     */
    public function roots(): array
    {
        return $this->roots ??= LayersConfig::modelRoots();
    }

    /**
     * Every model found in the configured directories.
     *
     * @return Collection<int, ModelReference>
     */
    public function all(): Collection
    {
        if ($this->models !== null) {
            return $this->models;
        }

        $models = collect();

        foreach ($this->roots() as $root) {
            if (! $root->exists()) {
                continue;
            }

            foreach ((new Finder)->files()->name('*.php')->in($root->directory)->sortByName() as $file) {
                $class = $this->classIn($file->getContents());

                if ($class === null || $class['name'] !== $file->getBasename('.php')) {
                    continue;
                }

                $models->push(new ModelReference(
                    name: $class['name'],
                    subpath: Paths::clean($file->getRelativePath()),
                    root: $root,
                    fqcn: ltrim($class['namespace'] . '\\' . $class['name'], '\\'),
                    exists: true,
                ));
            }
        }

        return $this->models = $models;
    }

    /**
     * Resolve a model from a name like "User", "Auth/Token", "Core/User", "Auth.Token"
     * or a fully-qualified class name. Unknown models are placed where they would live.
     *
     * @throws \InvalidArgumentException
     */
    public function resolve(string $input): ModelReference
    {
        $path = trim(str_replace(['\\', '.'], '/', trim($input)), '/');

        if ($path === '' || str_contains($path, '//') || preg_match('#[^A-Za-z0-9_/]#', $path)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $models = $this->all();

        $matches = $models->filter(fn (ModelReference $model): bool => str_replace('\\', '/', $model->fqcn) === $path);

        if ($matches->isEmpty()) {
            $matches = $models->filter(fn (ModelReference $model): bool => $model->identity() === $path);
        }

        if ($matches->isEmpty()) {
            $matches = $models->filter(
                fn (ModelReference $model): bool => str_ends_with('/' . $model->qualifiedIdentity(), '/' . $path)
            );
        }

        if ($matches->count() > 1) {
            throw new InvalidArgumentException(sprintf(
                'Model [%s] is ambiguous. Use one of: %s.',
                $input,
                $matches->map(fn (ModelReference $model): string => $model->qualifiedIdentity())->implode(', ')
            ));
        }

        return $matches->first() ?? $this->guess($path, $input);
    }

    /**
     * Place a model that does not exist (yet) in the most specific models directory.
     *
     * @throws \InvalidArgumentException
     */
    private function guess(string $path, string $input): ModelReference
    {
        $segments = explode('/', $path);
        $name = array_pop($segments);
        $candidates = [];
        $longest = 0;

        # "Core/Name" and "Modules/Core/Name" both point to app/Modules/Core/Models
        foreach (array_merge($this->roots(), $this->pendingRoots($segments)) as $root) {
            $qualifier = $root->qualifier === '' ? [] : explode('/', $root->qualifier);

            for ($i = 0; $i < count($qualifier); $i++) {
                $suffix = array_slice($qualifier, $i);
                $length = count($suffix);

                if ($length > count($segments) || array_slice($segments, 0, $length) !== $suffix) {
                    continue;
                }

                if ($length > $longest) {
                    $candidates = [];
                    $longest = $length;
                }

                if ($length === $longest) {
                    $candidates[] = $root;
                }

                break;
            }
        }

        if (count($candidates) > 1) {
            throw new InvalidArgumentException(sprintf(
                'Model [%s] is ambiguous. Use one of: %s.',
                $input,
                implode(', ', array_map(fn (ModelRoot $root): string => $root->qualifier . '/' . $name, $candidates))
            ));
        }

        $root = $candidates[0] ?? $this->defaultRoot($input, $name);
        $subpath = implode('/', array_slice($segments, $longest));

        return new ModelReference(
            name: $name,
            subpath: $subpath,
            root: $root,
            fqcn: implode('\\', array_filter([$root->namespace(), str_replace('/', '\\', $subpath), $name])),
            exists: false,
        );
    }

    /**
     * Models directories not created yet inside folders matched by a glob pattern,
     * e.g. app/Modules/Fiscal/Models for "Fiscal/Nota" when app/Modules/Fiscal exists.
     *
     * @param  array<int, string>  $segments
     * @return array<int, ModelRoot>
     */
    private function pendingRoots(array $segments): array
    {
        $roots = [];

        foreach (LayersConfig::modelPatterns() as $pattern) {
            if (substr_count($pattern, '*') !== 1 || ! in_array('*', explode('/', $pattern), true)) {
                continue;
            }

            foreach (array_unique($segments) as $segment) {
                $directory = str_replace('*', $segment, $pattern);

                if (! is_dir($directory) && is_dir(dirname($directory))) {
                    $roots[] = new ModelRoot($directory, glob: true);
                }
            }
        }

        return $roots;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function defaultRoot(string $input, string $name): ModelRoot
    {
        foreach ($this->roots() as $root) {
            if (! $root->glob) {
                return $root;
            }
        }

        $example = isset($this->roots()[0]) ? basename($this->roots()[0]->qualifier) . '/' . $name : 'Module/' . $name;

        throw new InvalidArgumentException(sprintf(
            'Model [%s] was not found. Qualify it with the folder it belongs to, e.g. [%s].',
            $input,
            $example
        ));
    }

    /**
     * Namespace and name of the concrete class declared in a PHP file.
     *
     * @return array{namespace: string, name: string}|null
     */
    private function classIn(string $contents): ?array
    {
        if (! preg_match('/^\s*(?:(?:final|readonly)\s+)*class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $class)) {
            return null;
        }

        preg_match('/^\s*namespace\s+([^;\s{]+)/m', $contents, $namespace);

        return ['namespace' => $namespace[1] ?? '', 'name' => $class[1]];
    }
}
