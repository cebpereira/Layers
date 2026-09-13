<?php

declare(strict_types=1);

namespace CebPereira\Layers\Console\Commands;

use CebPereira\Layers\Console\Concerns\GeneratesLayers;
use CebPereira\Layers\Support\LayerTarget;
use CebPereira\Layers\Support\ModelLocator;
use Illuminate\Console\Command;
use InvalidArgumentException;

class MakeRepository extends Command
{
    use GeneratesLayers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        layers:repository {name}
        {--e|eloquent : Generate a repository eloquent for the model}
        {--i|interface : Generate a repository interface for the model}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a repository file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ModelLocator $locator): int
    {
        try {
            $layer = $this->layer();
            $model = $locator->resolve((string) $this->argument('name'));
            $interface = LayerTarget::for('interface', $model);
            $target = LayerTarget::for($layer, $model);
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }

        if (! $model->exists) {
            $this->components->warn(sprintf('Model [%s] was not found.', $model->fqcn));
        }

        $imports = $layer === 'eloquent' ? [$model->fqcn, $interface->fqcn()] : [$model->fqcn];

        $created = $this->writeLayer('Repository file', $target, 'Repository' . ucfirst($layer), [
            'imports' => $this->imports($target->namespace, $imports),
            'model' => $model->name,
            'modelFqcn' => $model->fqcn,
            'modelVariable' => lcfirst($model->name),
            'interface' => $interface->class,
            'interfaceFqcn' => $interface->fqcn(),
        ]);

        return $created ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Get the repository layer.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function layer(): string
    {
        $options = $this->options();

        if ($options['eloquent'] && $options['interface']) {
            throw new InvalidArgumentException('More than one option provided: expected \'eloquent\' or \'interface\', not both.');
        } elseif ($options['eloquent']) {
            return 'eloquent';
        } elseif ($options['interface']) {
            return 'interface';
        }

        throw new InvalidArgumentException('Invalid option: expected \'eloquent\' or \'interface\'.');
    }
}
