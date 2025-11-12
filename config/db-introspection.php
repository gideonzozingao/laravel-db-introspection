<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Namespace for Generated Models
    |--------------------------------------------------------------------------
    |
    | Default namespace (used when --namespace not provided). Use PHP style
    | namespace, e.g. "App\\Models" or "App\\Domain\\Models".
    |
    */

    'namespace' => env('DB_INTROSPECTION_NAMESPACE', 'App\\Models'),

    /*
    |--------------------------------------------------------------------------
    | Target path for generated models (relative to project base path)
    |--------------------------------------------------------------------------
    |
    | Example: "app" will write to base_path('app/...'). You can change to
    | "src/Models" or any other path.
    |
    */

    'target_path' => env('DB_INTROSPECTION_TARGET_PATH', 'app'),

    /*
    |--------------------------------------------------------------------------
    | Tables to ignore by default
    |--------------------------------------------------------------------------
    */

    'ignore_tables' => [
        'migrations',
        'password_resets',
        'failed_jobs',
        'personal_access_tokens',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default options
    |--------------------------------------------------------------------------
    */

    'with_inverse' => env('DB_INTROSPECTION_WITH_INVERSE', true),
    'with_phpdoc'  => env('DB_INTROSPECTION_WITH_PHPDOC', true),
];
