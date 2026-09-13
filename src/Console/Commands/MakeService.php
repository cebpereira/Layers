<?php

declare(strict_types=1);

namespace CebPereira\Layers\Console\Commands;

use CebPereira\Layers\Console\Concerns\GeneratesLayers;
use CebPereira\Layers\Support\BindingScanner;
use CebPereira\Layers\Support\LayerTarget;
use CebPereira\Layers\Support\ModelLocator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class MakeService extends Command
{
    use GeneratesLayers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        layers:service {name}
        {--wr=* : Repositories injected into the service (defaults to the service model)}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a service file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ModelLocator $locator, BindingScanner $scanner): int
    {
        $name = (string) $this->argument('name');

        try {
            $target = LayerTarget::for('service', $locator->resolve($name));

            $repositories = collect($this->option('wr') ?: [$name])
                ->map(fn (string $repository): array => $this->repository($repository, $locator, $scanner));
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return Command::FAILURE;
        }

        $created = $this->writeLayer('Service file', $target, 'Service', [
            'imports' => $this->imports(
                $target->namespace,
                $repositories->map(fn (array $repository): string => $repository['interface']->fqcn())->all()
            ),
            'parameters' => $repositories
                ->map(fn (array $repository): string => sprintf(
                    '        protected %s $repo%s,',
                    $repository['interface']->class,
                    $repository['model']
                ))
                ->implode("\n"),
        ]);

        return $created ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Find the repository interface of a model.
     *
     * @return array{model: string, interface: LayerTarget}
     *
     * @throws \InvalidArgumentException
     */
    protected function repository(string $name, ModelLocator $locator, BindingScanner $scanner): array
    {
        $model = $locator->resolve($name);
        $interface = LayerTarget::for('interface', $model);

        if (File::exists($interface->path)) {
            return ['model' => $model->name, 'interface' => $interface];
        }

        # Repositories generated without a matching model file
        $candidates = collect($scanner->interfaces())
            ->filter(fn (array $candidate): bool => $candidate['target']->class === $interface->class)
            ->values();

        if ($candidates->count() > 1) {
            throw new InvalidArgumentException(sprintf(
                'Repository [%s] is ambiguous. Use one of: %s.',
                $interface->class,
                $candidates->map(fn (array $candidate): string => $candidate['target']->fqcn())->implode(', ')
            ));
        }

        if ($candidates->isEmpty()) {
            throw new InvalidArgumentException('Repository not found: ' . $interface->fqcn());
        }

        return ['model' => $model->name, 'interface' => $candidates->first()['target']];
    }
}
