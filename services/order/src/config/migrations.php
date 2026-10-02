<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Entity Manager Migrations Configuration
    |--------------------------------------------------------------------------
    |
    | Each entity manager has its own migration configuration.
    | Laravel migrations live in `database/migrations` and use the standard
    | `php artisan migrate` command. Doctrine migrations live separately in
    | `database/doctrine-migrations` and use `php artisan doctrine:migrations:*`.
    |
    */
    'default' => [
        'table_storage' => [
            'table_name' => 'doctrine_migration_versions',
            'version_column_name' => 'version',
            'version_column_length' => 255,
            'executed_at_column_name' => 'executed_at',
            'execution_time_column_name' => 'execution_time',
            'schema_filter' => '/^(orders|payments|payouts|outbox|saga_states)$/',
        ],

        'migrations_paths' => [
            'Database\\DoctrineMigrations' => database_path('doctrine-migrations'),
        ],

        'organize_migrations' => 'none',
        'all_or_nothing' => false,
        'transactional' => true,
        'check_database_platform' => true,
    ],
];
