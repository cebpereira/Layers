<?php

declare(strict_types=1);

namespace CebPereira\Layers\Support;

use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

final class BindingScanner
{
    /**
     * Repository interfaces bound to their existing Eloquent implementations.
     *
     * @return array<string, string>
     */
    public function bindings(): array
    {
        $bindings = [];

        foreach ($this->interfaces() as $interface) {
            $concrete = LayerTarget::make('eloquent', $interface['root'], $interface['subpath'], $interface['model']);

            if (is_file($concrete->path)) {
                $bindings[$interface['target']->fqcn()] = $concrete->fqcn();
            }
        }

        return $bindings;
    }

    /**
     * Repository interface files found next to every models directory.
     *
     * @return array<int, array{target: LayerTarget, root: ModelRoot, subpath: string, model: string}>
     */
    public function interfaces(): array
    {
        $structure = LayersConfig::structure('interface');
        $pattern = $this->pattern($structure);
        $prefix = $this->staticPrefix($structure['path']);
        $interfaces = [];
        $scanned = [];

        foreach (LayersConfig::modelRoots() as $root) {
            $directory = $root->baseDirectory . ($prefix === '' ? '' : '/' . $prefix);

            if (isset($scanned[$directory]) || ! is_dir($directory)) {
                continue;
            }

            $scanned[$directory] = true;

            try {
                foreach ((new Finder)->files()->name('*.php')->in($directory)->sortByName() as $file) {
                    $relative = Paths::clean($prefix . '/' . substr($file->getRelativePathname(), 0, -4));

                    if (! preg_match($pattern, $relative, $matches) || ! isset($matches['model'])) {
                        continue;
                    }

                    $subpath = $matches['subpath'] ?? '';
                    $target = LayerTarget::make('interface', $root, $subpath, $matches['model']);

                    if ($target->path !== $root->baseDirectory . '/' . $relative . '.php') {
                        continue;
                    }

                    $interfaces[] = ['target' => $target, 'root' => $root, 'subpath' => $subpath, 'model' => $matches['model']];
                }
            } catch (InvalidArgumentException) {
                # The directory is not mapped to a PSR-4 namespace
                continue;
            }
        }

        return $interfaces;
    }

    /**
     * Regex matching a file path (relative to the base directory, without extension).
     *
     * @param  array{path: string, class: string}  $structure
     */
    private function pattern(array $structure): string
    {
        $regex = '';

        foreach (explode('/', Paths::clean($structure['path'])) as $segment) {
            if ($segment === '') {
                continue;
            }

            $regex .= $segment === '{subpath}'
                ? '(?:(?<subpath>.+?)/)?'
                : $this->placeholders($segment) . '/';
        }

        return '#(?J)^' . $regex . $this->placeholders($structure['class']) . '$#';
    }

    private function placeholders(string $template): string
    {
        return str_replace(
            ['\{model\}', '\{subpath\}'],
            ['(?<model>[A-Za-z_][A-Za-z0-9_]*)', '(?<subpath>.+?)'],
            preg_quote($template, '#')
        );
    }

    /**
     * Leading folders of a path template that contain no placeholder.
     */
    private function staticPrefix(string $path): string
    {
        $prefix = [];

        foreach (explode('/', Paths::clean($path)) as $segment) {
            if (str_contains($segment, '{')) {
                break;
            }

            $prefix[] = $segment;
        }

        return implode('/', $prefix);
    }
}
