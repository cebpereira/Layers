<?php

declare(strict_types=1);

namespace CebPereira\Layers\Console\Commands;

use CebPereira\Layers\Support\BindingScanner;
use Illuminate\Console\Command;

class ListBinds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'layers:binds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all binds from application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(BindingScanner $scanner): int
    {
        $bindings = $scanner->bindings();

        if ($bindings === []) {
            $this->components->info('No repository bindings found.');

            return Command::SUCCESS;
        }

        # "Registered" tells whether the interface is bound in the container,
        # automatically (layers.auto_bind) or by a service provider
        $this->table(
            ['Interface', 'Implementation', 'Registered'],
            collect($bindings)
                ->map(fn (string $concrete, string $abstract): array => [
                    $abstract,
                    $concrete,
                    $this->laravel->bound($abstract) ? 'yes' : 'no',
                ])
                ->values()
                ->all()
        );

        return Command::SUCCESS;
    }
}
