# Layers

A laravel package to generate files for layered architecture and automate interface bindings.

**Recommended Laravel version:** `^12.0`

Go to [Laravel Docs](https://laravel.com/docs/releases#support-policy) to see support policy.

## Summary
- <a href="#requirements">Requirements</a>
- <a href="#installation">Installation</a>
- <a href="#configuration">Configuration</a>
- <a href="#usage">Usage</a>
  - <a href="#generate-layers">Generate Layers</a>
  - <a href="#generate-layers-with-subfolders">Generate Layers with Subfolders</a>
  - <a href="#generate-services-with-more-than-one-repository">Generate Services with more than one repository</a>
  - <a href="#scaffold-layers-from-models">Scaffold Layers from Models</a>

## Requirements

```json
"php": "^8.2"
"symfony/finder": "^6.3 || ^7.0"
"illuminate/support": "^9.0 || ^10.20 || ^11.0 || ^12.0"
"illuminate/console": "^9.0 || ^10.20 || ^11.0 || ^12.0"
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

    'namespace' => [
        'repositories' => 'Repositories',
        'services' => 'Services',
    ],

    'path' => [
        'models' => app_path('Models'),
        'repositories' => app_path('Repositories'),
        'services' => app_path('Services'),
    ]
];

```

Into this file, you can switch the services/repositories default path. For this, keep the namespace and path keys both equals.

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
- `php artisan layers:binds` : List all binds from application
- `php artisan layers:scaffold` : Scaffold repositories and services for all models

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

### Generate Layers with Subfolders
```bash
php artisan layers --repository User.Address
```

**This command will generate 2 files:**
- app/Repositories/User/AddressRepositoryInterface.php
- app/Repositories/User/AddressRepositoryEloquent.php

<img src="./assets/structure_folder_with_subfolders.png" alt="Structure Folder with Subfolders" />

### Generate Services with more than one repository
```bash
php artisan layers --service --wr=Address --wr=User Person
```

**This command will generate the follow file:**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use App\Repositories\User\AddressRepositoryInterface;

class PersonService
{
    private $repoAddress;
    private $repoUser;

    public function __construct(
        AddressRepositoryInterface $repoAddress,
        UserRepositoryInterface $repoUser,
    ) {
        $this->repoAddress = $repoAddress;
        $this->repoUser = $repoUser;
    }

    // Add your functions here...
}
```

### Scaffold Layers from Models

Instead of generating files one by one, you can scaffold repositories for all models at once:

```bash
php artisan layers:scaffold
```

This command scans the models directory (configured in `layers.path.models`) and generates an Interface and Eloquent pair for every model found.

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
