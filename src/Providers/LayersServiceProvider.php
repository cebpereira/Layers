<?php

declare(strict_types=1);

namespace WilliamJSS\Layers\Providers;

use Illuminate\Support\ServiceProvider;
use WilliamJSS\Layers\Console\Commands\MakeLayer;
use WilliamJSS\Layers\Console\Commands\MakeRepository;
use WilliamJSS\Layers\Console\Commands\MakeService;
use WilliamJSS\Layers\Console\Commands\ListBinds;
use WilliamJSS\Layers\Console\Commands\ScaffoldLayers;

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
