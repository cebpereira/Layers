# Layers

A laravel package to generate files for layered architecture and automate interface bindings.

**Recommended Laravel version:** `^13.0`

Go to [Laravel Docs](https://laravel.com/docs/releases#support-policy) to see support policy.

## Summary
- <a href="#requirements">Requirements</a>
- <a href="#installation">Installation</a>
- <a href="#configuration">Configuration</a>
- <a href="#usage">Usage</a>
  - <a href="#model-names">Model Names</a>
  - <a href="#generate-layers">Generate Layers</a>
  - <a href="#models-in-subfolders">Models in Subfolders</a>
  - <a href="#generate-services-with-more-than-one-repository">Generate Services with more than one repository</a>
  - <a href="#scaffold-layers-from-models">Scaffold Layers from Models</a>
- <a href="#modular-applications">Modular Applications</a>
- <a href="#bindings">Bindings</a>
- <a href="#customizing-stubs">Customizing Stubs</a>
- <a href="#upgrading-to-14">Upgrading to 1.4</a>

## Requirements

```json
"php": "^8.2"
"symfony/finder": "^6.3 || ^7.0 || ^8.0"
"illuminate/support": "^9.0 || ^10.20 || ^11.0 || ^12.0 || ^13.0"
"illuminate/console": "^9.0 || ^10.20 || ^11.0 || ^12.0 || ^13.0"
```

## Installation

```bash
composer require cebpereira/layers --dev
```

## Configuration

```bash
php artisan vendor:publish --tag=layers
```

**This command will copy Layers config to your project config folder**

```php
<?php

return [

    'models' => [
        app_path('Models'),
        // app_path('Modules/*/Models'),
    ],

    'structure' => [
        'interface' => [
            'path' => 'Repositories/{subpath}',
            'class' => '{model}RepositoryInterface',
        ],
        'eloquent' => [
            'path' => 'Repositories/{subpath}',
            'class' => '{model}RepositoryEloquent',
        ],
        'service' => [
            'path' => 'Services/{subpath}',
            'class' => '{model}Service',
        ],
    ],

    'auto_bind' => true,

];
```

- **models** : directories where your models live. Glob patterns are accepted.
- **structure** : folder and class name of each layer. The folder is relative to the **parent of the models directory**, so `app/Models` generates layers inside `app`, and `app/Modules/Core/Models` generates layers inside `app/Modules/Core`.
  - `{subpath}` : the model subfolder (`Auth` for `app/Models/Auth/Token.php`)
  - `{model}` : the model name (`Token`)
- **auto_bind** : bind every repository interface to its eloquent implementation. See <a href="#bindings">Bindings</a>.

Namespaces are resolved from the PSR-4 mappings in your `composer.json`.

## Usage

Using the `layers` artisan command, we can be generate files for repositories (interface and eloquent) and services.

`php artisan layers` + `{option}` + `{model name}`

Available options:

- **-e** or **--eloquent** : Generate a repository eloquent for the model
- **-i** or **--interface** : Generate a repository interface for the model
- **-s** or **--service** : Generate a service for the model
- **-r** or **--repository** : Generate a repository interface and eloquent for the model
- **-a** or **--all** : Generate a service, repository interface and repository eloquent for the model
- **--wr** : Specify the service's repositories

*Subcommands*
- `php artisan layers:repository --eloquent` : the same as ***php artisan layers --eloquent***
- `php artisan layers:repository --interface` : the same as ***php artisan layers --interface***
- `php artisan layers:service` : the same as ***php artisan layers --service***
- `php artisan layers:binds` : List all repository bindings
- `php artisan layers:scaffold` : Scaffold repositories and services for all models

### Model Names

The model is searched in the configured `models` directories, and can be written as:

| Name | Model |
|---|---|
| `User` | `app/Models/User.php` |
| `Auth/Token` or `Auth.Token` | `app/Models/Auth/Token.php` |
| `Core/User` or `Modules/Core/User` | `app/Modules/Core/Models/User.php` |
| `App\Modules\Core\Models\User` | `app/Modules/Core/Models/User.php` |

If a name matches more than one model, the command fails and lists the options. If the model does not exist, the files are generated where it would live and a warning is displayed.

### Generate Layers
```bash
php artisan layers --all User
```

**This command will generate 3 files:**
- app/Repositories/UserRepositoryInterface.php
- app/Repositories/UserRepositoryEloquent.php
- app/Services/UserService.php

<img src="./assets/structure_folder.png" alt="Structure Folder" />

#### UserRepositoryInterface.php
```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface UserRepositoryInterface
{
    public function __construct(User $user);

    /**
     * Stores a new instance of User in the database
     * @param SupportCollection|array|int|string $data
     * @return User
     */
    public function store(SupportCollection|array|int|string $data): User;

    /**
     * Returns all instances of User from the database
     * @param array|string $columns
     * @param array<array>|null $filters
     * @return Collection<int, User>
     */
    public function getList(array|string $columns = ['*'], ?array $filters = null): Collection;

    /**
     * Returns an instance of User from the given id
     * @param int|string $id
     * @return User|null
     */
    public function get(int|string $id): ?User;

    /**
     * Updates the data of an instance of User
     * @param SupportCollection|array|int|string $data
     * @param int|string $id
     * @return User
     */
    public function update(SupportCollection|array|int|string $data, int|string $id): User;

    /**
     * Removes an instance of User from the database
     * @param int|string $id
     * @return bool
     */
    public function destroy(int|string $id): bool;
}
```

#### UserRepositoryEloquent.php
```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class UserRepositoryEloquent implements UserRepositoryInterface
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * Stores a new instance of User in the database
     * @param SupportCollection|array|int|string $data
     * @return User
     */
    public function store(SupportCollection|array|int|string $data): User
    {
        return $this->user->create($data);
    }

    /**
     * Returns all instances of User from the database
     * @param array|string $columns
     * @param array<array>|null $filters
     * @return Collection<int, User>
     */
    public function getList(array|string $columns = ['*'], ?array $filters = null): Collection
    {
        $query = $this->user->newQuery();

        if ($filters) {
            $query->where($filters);
        }

        return $query->get($columns);
    }

    /**
     * Returns an instance of User from the given id
     * @param int|string $id
     * @return User|null
     */
    public function get(int|string $id): ?User
    {
        return $this->user->find($id);
    }

    /**
     * Updates the data of an instance of User
     * @param SupportCollection|array|int|string $data
     * @param int|string $id
     * @return User
     */
    public function update(SupportCollection|array|int|string $data, int|string $id): User
    {
        $user = $this->user->findOrFail($id);
        $user->update($data);

        return $user;
    }

    /**
     * Removes an instance of User from the database
     * @param int|string $id
     * @return bool
     */
    public function destroy(int|string $id): bool
    {
        return (bool) $this->user->findOrFail($id)->delete();
    }
}
```

#### UserService.php
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepositoryInterface;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $repoUser,
    ) {}

    // Add your functions here...
}
```

### Models in Subfolders
```bash
php artisan layers --repository Auth/Token
```

**This command will generate 2 files, mirroring the model subfolder:**
- app/Repositories/Auth/TokenRepositoryInterface.php
- app/Repositories/Auth/TokenRepositoryEloquent.php

Both files import the model from its real namespace (`use App\Models\Auth\Token;`).

<img src="./assets/structure_folder_with_subfolders.png" alt="Structure Folder with Subfolders" />

### Generate Services with more than one repository
```bash
php artisan layers --service --wr=Auth/Token --wr=User Person
```

**This command will generate the follow file:**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Auth\TokenRepositoryInterface;
use App\Repositories\UserRepositoryInterface;

class PersonService
{
    public function __construct(
        protected TokenRepositoryInterface $repoToken,
        protected UserRepositoryInterface $repoUser,
    ) {}

    // Add your functions here...
}
```

The repositories must exist before generating the service.

### Scaffold Layers from Models

Instead of generating files one by one, you can scaffold repositories for all models at once:

```bash
php artisan layers:scaffold
```

This command scans every configured `models` directory and generates an Interface and Eloquent pair for every model found.

**Example** — given the following models:

```
app/Models/
├── User.php
├── Company.php
└── Auth/
    └── Token.php
```

Running `layers:scaffold` generates:

```
app/Repositories/
├── UserRepositoryInterface.php
├── UserRepositoryEloquent.php
├── CompanyRepositoryInterface.php
├── CompanyRepositoryEloquent.php
└── Auth/
    ├── TokenRepositoryInterface.php
    └── TokenRepositoryEloquent.php
```

To also generate a service for each model, use the `--with-service` flag:

```bash
php artisan layers:scaffold --with-service
```

If a file already exists, it will be skipped automatically — no files are overwritten.

## Modular Applications

Layers are generated next to the models directory, so modules work without extra commands. Given this structure:

```
app/Modules/
├── Core/
│   └── Models/
│       └── User.php
└── Billing/
    └── Models/
        └── Invoice.php
```

And this configuration:

```php
'models' => [
    app_path('Modules/*/Models'),
],

'structure' => [
    'interface' => [
        'path' => 'Repositories/Contracts/{subpath}',
        'class' => '{model}RepositoryInterface',
    ],
    'eloquent' => [
        'path' => 'Repositories/Eloquent/{subpath}',
        'class' => 'Eloquent{model}Repository',
    ],
    'service' => [
        'path' => 'Services/{subpath}',
        'class' => '{model}Service',
    ],
],
```

Running `php artisan layers --repository Billing/Invoice` generates:
- app/Modules/Billing/Repositories/Contracts/InvoiceRepositoryInterface.php
- app/Modules/Billing/Repositories/Eloquent/EloquentInvoiceRepository.php

Names without a model can be placed in a module by prefixing it:

```bash
php artisan layers:service Core/Report --wr=Core/User --wr=Billing/Invoice
# app/Modules/Core/Services/ReportService.php
```

## Bindings

With `auto_bind` enabled, every repository interface is bound to its eloquent implementation, following the configured `structure`.

If you prefer to register bindings in your own service providers (e.g. one provider per module), disable it:

```php
'auto_bind' => false,
```

`php artisan layers:binds` lists every interface/implementation pair found and whether it is registered in the container:

```
+---------------------------------------------------+-------------------------------------------------+------------+
| Interface                                         | Implementation                                  | Registered |
+---------------------------------------------------+-------------------------------------------------+------------+
| App\Repositories\Auth\TokenRepositoryInterface    | App\Repositories\Auth\TokenRepositoryEloquent   | yes        |
| App\Repositories\UserRepositoryInterface          | App\Repositories\UserRepositoryEloquent         | yes        |
+---------------------------------------------------+-------------------------------------------------+------------+
```

## Customizing Stubs

```bash
php artisan vendor:publish --tag=layers-stubs
```

The stubs are copied to `stubs/layers` and used instead of the package ones.

| Stub | Placeholders |
|---|---|
| `RepositoryInterface.stub` | `namespace`, `class`, `imports`, `model`, `modelFqcn`, `modelVariable` |
| `RepositoryEloquent.stub` | `namespace`, `class`, `imports`, `model`, `modelFqcn`, `modelVariable`, `interface`, `interfaceFqcn` |
| `Service.stub` | `namespace`, `class`, `imports`, `parameters` |

## Upgrading to 1.4

- Config files published by older versions (`namespace` and `path` keys) still work. Publish the new config to use `models`, `structure` and `auto_bind`.
- Generated repositories import the model from its real namespace. `php artisan layers --repository User.Address` now imports `App\Models\User\Address` instead of `App\Models\Address`.
- Services with more than one repository use constructor property promotion, and the `ServiceMultiRepositories.stub` was removed.
