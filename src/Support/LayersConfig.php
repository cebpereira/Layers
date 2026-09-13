<?php

declare(strict_types=1);

namespace CebPereira\Layers\Support;

use InvalidArgumentException;

final class LayersConfig
{
    /**
     * Where each layer is written, relative to the parent of the models directory.
     *
     * @var array<string, array{path: string, class: string}>
     */
    public const DEFAULT_STRUCTURE = [
        'interface' => [
            'path' => 'Repositories/{subpath}',
            'class' => '{model}RepositoryInterface',
        ],
        'eloquent' => [
            'path' => 'Repositories/{subpath}',
            'class' => '{model}RepositoryEloquent',
        ],
        'service' => [
            'path' => 'Services/{subpath}',
            'class' => '{model}Service',
        ],
    ];

    /**
     * Configured model directories, with glob patterns expanded.
     *
     * @return array<int, ModelRoot>
     */
    public static function modelRoots(): array
    {
        $roots = [];

        foreach (self::modelPatterns() as $pattern) {
            if (strpbrk($pattern, '*?[') === false) {
                $roots[$pattern] ??= new ModelRoot($pattern, glob: false);

                continue;
            }

            foreach (glob($pattern, GLOB_ONLYDIR) ?: [] as $directory) {
                $directory = Paths::normalize($directory);
                $roots[$directory] ??= new ModelRoot($directory, glob: true);
            }
        }

        return array_values($roots);
    }

    /**
     * Configured model directories, as written in the config.
     *
     * @return array<int, string>
     */
    public static function modelPatterns(): array
    {
        # "path.models" comes from config files published before 1.4
        $patterns = config('layers.path.models') ?? config('layers.models') ?? app_path('Models');

        return array_map(fn ($pattern): string => Paths::normalize((string) $pattern), array_values((array) $patterns));
    }

    /**
     * Path and class name templates of a layer.
     *
     * @return array{path: string, class: string}
     *
     * @throws \InvalidArgumentException
     */
    public static function structure(string $layer): array
    {
        $structure = self::DEFAULT_STRUCTURE[$layer]
            ?? throw new InvalidArgumentException("Unknown layer [{$layer}].");

        # Config files published before 1.4 define folders through "namespace"
        $legacy = config('layers.namespace');

        if (is_array($legacy)) {
            $key = $layer === 'service' ? 'services' : 'repositories';

            if (is_string($legacy[$key] ?? null)) {
                $structure['path'] = Paths::clean($legacy[$key]) . '/{subpath}';
            }

            return $structure;
        }

        $configured = config("layers.structure.{$layer}");

        return array_replace($structure, is_array($configured) ? array_filter($configured, 'is_string') : []);
    }

    /**
     * Modifiers of the properties promoted in generated constructors.
     *
     * @throws \InvalidArgumentException
     */
    public static function propertyModifiers(): string
    {
        $value = (string) config('layers.property_modifiers', 'protected');
        $modifiers = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $valid = $modifiers !== []
            && array_diff($modifiers, ['public', 'protected', 'private', 'readonly']) === []
            && count(array_intersect($modifiers, ['public', 'protected', 'private'])) <= 1
            && count($modifiers) === count(array_unique($modifiers));

        if (! $valid) {
            throw new InvalidArgumentException(sprintf(
                'Invalid layers.property_modifiers [%s]: expected one visibility (public, protected, private) and/or readonly.',
                $value
            ));
        }

        return implode(' ', $modifiers);
    }

    public static function autoBind(): bool
    {
        return (bool) config('layers.auto_bind', true);
    }
}
