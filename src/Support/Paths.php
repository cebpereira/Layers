<?php

declare(strict_types=1);

namespace CebPereira\Layers\Support;

final class Paths
{
    /**
     * Use forward slashes and strip the trailing separator.
     */
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * Normalized real path, or the normalized path itself when it does not exist.
     */
    public static function real(string $path): string
    {
        return self::normalize(realpath($path) ?: $path);
    }

    /**
     * Collapse a relative path, removing empty segments ("Repositories//Contracts/").
     */
    public static function clean(string $path): string
    {
        return implode('/', array_filter(
            explode('/', str_replace('\\', '/', $path)),
            fn (string $segment): bool => $segment !== ''
        ));
    }

    /**
     * Path of $path relative to $directory, or null when it lies outside of it.
     */
    public static function relative(string $directory, string $path): ?string
    {
        $directory = self::normalize($directory);
        $path = self::normalize($path);

        if ($path === $directory) {
            return '';
        }

        return str_starts_with($path, $directory . '/')
            ? substr($path, strlen($directory) + 1)
            : null;
    }
}
