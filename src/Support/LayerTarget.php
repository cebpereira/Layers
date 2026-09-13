<?php

declare(strict_types=1);

namespace CebPereira\Layers\Support;

final class LayerTarget
{
    private function __construct(
        public readonly string $class,
        public readonly string $namespace,
        public readonly string $path,
    ) {}

    /**
     * Class and file of a layer ("interface", "eloquent" or "service") for the given model.
     *
     * @throws \InvalidArgumentException
     */
    public static function for(string $layer, ModelReference $model): self
    {
        return self::make($layer, $model->root, $model->subpath, $model->name);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function make(string $layer, ModelRoot $root, string $subpath, string $model): self
    {
        $structure = LayersConfig::structure($layer);

        $directory = Paths::clean(str_replace(['{subpath}', '{model}'], [$subpath, $model], $structure['path']));
        $class = str_replace('{model}', $model, $structure['class']);

        return new self(
            class: $class,
            namespace: $root->baseNamespace() . ($directory === '' ? '' : '\\' . str_replace('/', '\\', $directory)),
            path: $root->baseDirectory . '/' . ($directory === '' ? '' : $directory . '/') . $class . '.php',
        );
    }

    public function fqcn(): string
    {
        return $this->namespace . '\\' . $this->class;
    }
}
