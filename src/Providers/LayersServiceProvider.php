<?php

declare(strict_types=1);

namespace CebPereira\Layers\Providers;

use Illuminate\Support\ServiceProvider;
use CebPereira\Layers\Console\Commands\MakeLayer;
use CebPereira\Layers\Console\Commands\MakeRepository;
use CebPereira\Layers\Console\Commands\MakeService;
use CebPereira\Layers\Console\Commands\ListBinds;
use CebPereira\Layers\Console\Commands\ScaffoldLayers;

class LayersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RepositoryBindServiceProvider::class);

        $this->mergeConfigFrom(
            __DIR__.'/../config/layers.php', 'layers'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeLayer::class,
                MakeRepository::class,
                MakeService::class,
                ListBinds::class,
                ScaffoldLayers::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/layers.php' => config_path('layers.php')
            ], 'layers');
        }
    }
}
