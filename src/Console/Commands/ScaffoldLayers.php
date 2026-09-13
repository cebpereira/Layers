<?php

declare(strict_types=1);

namespace CebPereira\Layers\Console\Commands;

use CebPereira\Layers\Support\ModelLocator;
use CebPereira\Layers\Support\ModelRoot;
use Illuminate\Console\Command;

class ScaffoldLayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        layers:scaffold
        {--s|with-service : Also generate a service for each model}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold repository interface and eloquent for every model found in the models directory';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ModelLocator $locator): int
    {
        $roots = collect($locator->roots());

        if (! $roots->contains(fn (ModelRoot $root): bool => $root->exists())) {
            $this->error('Models directory not found: ' . ($roots->map->directory->implode(', ') ?: 'check layers.models'));

            return Command::FAILURE;
        }

        $models = $locator->all();

        if ($models->isEmpty()) {
            $this->warn('No models found in: ' . $roots->map->directory->implode(', '));

            return Command::SUCCESS;
        }

        $withService = $this->option('with-service');

        $this->info('Scaffolding layers...');
        $this->newLine();

        foreach ($models as $model) {
            $this->line($model->qualifiedIdentity());

            $this->call('layers:repository', ['name' => $model->fqcn, '--interface' => true]);
            $this->call('layers:repository', ['name' => $model->fqcn, '--eloquent' => true]);

            if ($withService) {
                $this->call('layers:service', ['name' => $model->fqcn]);
            }

            $this->newLine();
        }

        $this->info('Done.');

        return Command::SUCCESS;
    }
}
