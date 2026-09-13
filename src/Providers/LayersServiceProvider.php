<?php

declare(strict_types=1);

namespace CebPereira\Layers\Providers;

use Illuminate\Support\ServiceProvider;
use CebPereira\Layers\Console\Commands\MakeLayer;
use CebPereira\Layers\Console\Commands\MakeRepository;
use CebPereira\Layers\Console\Commands\MakeService;
use CebPereira\Layers\Console\Commands\ListBinds;
use CebPereira\Layers\Console\Commands\ScaffoldLayers;
use CebPereira\Layers\Support\ModelLocator;

class LayersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/layers.php', 'layers'
        );

        $this->app->singleton(ModelLocator::class);

        $this->app->register(RepositoryBindServiceProvider::class);
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

            $this->publishes([
                __DIR__.'/../Console/Commands/Stubs' => base_path('stubs/layers')
            ], 'layers-stubs');
        }
    }
}
