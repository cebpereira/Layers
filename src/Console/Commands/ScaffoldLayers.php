<?php

declare(strict_types=1);

namespace CebPereira\Layers\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

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
    public function handle(): int
    {
        $modelsPath = config('layers.path.models');

        if (! File::exists($modelsPath)) {
            $this->error("Models directory not found: {$modelsPath}");

            return Command::FAILURE;
        }

        $models = $this->resolveModels($modelsPath);

        if ($models->isEmpty()) {
            $this->warn('No models found in: ' . $modelsPath);

            return Command::SUCCESS;
        }

        $withService = $this->option('with-service');

        $this->info('Scaffolding layers...');
        $this->newLine();

        foreach ($models as $name) {
            $this->line($name);

            $this->call('layers:repository', ['name' => $name, '--interface' => true]);
            $this->call('layers:repository', ['name' => $name, '--eloquent' => true]);

            if ($withService) {
                $this->call('layers:service', ['name' => $name]);
            }

            $this->newLine();
        }

        $this->info('Done.');

        return Command::SUCCESS;
    }

    /**
     * Resolves model names relative to the models directory,
     * preserving subdirectory structure.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function resolveModels(string $modelsPath): \Illuminate\Support\Collection
    {
        return collect((new Finder)->files()->name('*.php')->in($modelsPath))
            ->map(function ($file) use ($modelsPath) {
                $relative = str_replace([$modelsPath . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());

                return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            })
            ->values();
    }
}
