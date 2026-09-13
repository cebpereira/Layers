<?php

declare(strict_types=1);

namespace CebPereira\Layers\Support;

use Composer\Autoload\ClassLoader;
use RuntimeException;

final class NamespaceResolver
{
    /**
     * @var array<string, string|null>
     */
    private static array $resolved = [];

    /**
     * @var array<string, array<int, string>>|null
     */
    private static ?array $prefixes = null;

    /**
     * Resolve the PSR-4 namespace of a directory from the registered Composer autoloaders.
     *
     * @param  string  $directory
     * @return string|null
     */
    public static function forDirectory(string $directory): ?string
    {
        $directory = Paths::real($directory);

        if (array_key_exists($directory, self::$resolved)) {
            return self::$resolved[$directory];
        }

        $namespace = null;
        $longest = -1;

        foreach (self::prefixes() as $prefix => $paths) {
            foreach ($paths as $path) {
                $relative = Paths::relative($path, $directory);

                if ($relative === null || strlen($path) <= $longest) {
                    continue;
                }

                $longest = strlen($path);
                $namespace = rtrim($prefix, '\\') . ($relative === '' ? '' : '\\' . str_replace('/', '\\', $relative));
            }
        }

        return self::$resolved[$directory] = $namespace;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function prefixes(): array
    {
        if (self::$prefixes !== null) {
            return self::$prefixes;
        }

        $prefixes = [];

        if (class_exists(ClassLoader::class, false) && method_exists(ClassLoader::class, 'getRegisteredLoaders')) {
            foreach (ClassLoader::getRegisteredLoaders() as $loader) {
                foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
                    $prefixes[$prefix] = array_merge($prefixes[$prefix] ?? [], $paths);
                }
            }
        }

        # Laravel's application namespace as a fallback
        try {
            $appNamespace = app()->getNamespace();
            $prefixes[$appNamespace] = array_merge($prefixes[$appNamespace] ?? [], [app_path()]);
        } catch (RuntimeException) {
            # composer.json does not map the application path
        }

        return self::$prefixes = array_map(
            fn (array $paths): array => array_map(fn (string $path): string => Paths::real($path), $paths),
            $prefixes
        );
    }
}
