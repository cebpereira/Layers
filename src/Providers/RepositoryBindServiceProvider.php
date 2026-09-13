<?php

declare(strict_types=1);

namespace CebPereira\Layers\Providers;

use CebPereira\Layers\Support\BindingScanner;
use CebPereira\Layers\Support\LayersConfig;
use Illuminate\Support\ServiceProvider;

class RepositoryBindServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! LayersConfig::autoBind()) {
            return;
        }

        # Bind repositories interfaces/eloquents
        foreach ((new BindingScanner)->bindings() as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
