<?php

declare(strict_types=1);

namespace CebPereira\Layers\Support;

use InvalidArgumentException;

/**
 * A directory that holds models. Layers are generated inside its parent (the "base"),
 * e.g. app/Models -> app, app/Modules/Core/Models -> app/Modules/Core.
 */
final class ModelRoot
{
    public readonly string $directory;

    public readonly string $baseDirectory;

    /**
     * Base directory relative to the application path ("" for app, "Modules/Core" for a module).
     */
    public readonly string $qualifier;

    public function __construct(string $directory, public readonly bool $glob)
    {
        $this->directory = Paths::normalize($directory);
        $this->baseDirectory = Paths::normalize(dirname($this->directory));
        $this->qualifier = Paths::relative(Paths::real(app_path()), Paths::real($this->baseDirectory))
            ?? basename($this->baseDirectory);
    }

    public function exists(): bool
    {
        return is_dir($this->directory);
    }

    /**
     * Namespace of the models directory.
     *
     * @throws \InvalidArgumentException
     */
    public function namespace(): string
    {
        return self::resolve($this->directory);
    }

    /**
     * Namespace of the base directory, where layers are generated.
     *
     * @throws \InvalidArgumentException
     */
    public function baseNamespace(): string
    {
        return self::resolve($this->baseDirectory);
    }

    private static function resolve(string $directory): string
    {
        return NamespaceResolver::forDirectory($directory)
            ?? throw new InvalidArgumentException(sprintf(
                'Could not resolve a PSR-4 namespace for [%s]. Map it in the "autoload" section of composer.json.',
                $directory
            ));
    }
}
