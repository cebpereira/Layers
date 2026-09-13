<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Directories where your models live. Glob patterns are accepted, so a
    | modular application can list every module at once (see below).
    |
    | Layers are generated inside the parent of each models directory and
    | mirror its subfolders: app/Models/Auth/Token.php generates files in
    | app/Repositories/Auth, while app/Modules/Core/Models/User.php
    | generates files in app/Modules/Core/Repositories.
    |
    */

    'models' => [
        app_path('Models'),
        // app_path('Modules/*/Models'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Structure
    |--------------------------------------------------------------------------
    |
    | Folder (relative to the parent of the models directory) and class name
    | of each layer. Available placeholders: {subpath} and {model}.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Automatic bindings
    |--------------------------------------------------------------------------
    |
    | Bind every repository interface to its Eloquent implementation. Disable
    | it when bindings are registered by your own service providers.
    |
    */

    'auto_bind' => true,

];
