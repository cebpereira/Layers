<?php

declare(strict_types=1);

namespace CebPereira\Layers\Support;

final class ModelReference
{
    /**
     * @param  string  $name  Class basename, e.g. "Token"
     * @param  string  $subpath  Subfolder inside the models directory, e.g. "Auth"
     * @param  string  $fqcn  Fully-qualified class name
     * @param  bool  $exists  Whether the model file was found
     */
    public function __construct(
        public readonly string $name,
        public readonly string $subpath,
        public readonly ModelRoot $root,
        public readonly string $fqcn,
        public readonly bool $exists,
    ) {}

    /**
     * Name relative to the models directory, e.g. "Auth/Token".
     */
    public function identity(): string
    {
        return Paths::clean($this->subpath . '/' . $this->name);
    }

    /**
     * Name prefixed by the base directory, e.g. "Modules/Core/User".
     */
    public function qualifiedIdentity(): string
    {
        return Paths::clean($this->root->qualifier . '/' . $this->identity());
    }
}
