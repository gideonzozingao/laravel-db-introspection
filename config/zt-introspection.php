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
    | Can be overridden via environment variable or command option.
    |
    */

    'namespace' => env('DB_INTROSPECTION_NAMESPACE', 'App\\Models'),

    /*
    |--------------------------------------------------------------------------
    | Target Path for Generated Models
    |--------------------------------------------------------------------------
    |
    | Target path for generated models (relative to project base path).
    | Example: "app" will write to base_path('app/...')
    | You can change to "src/Models" or any other path.
    |
    */

    'target_path' => env('DB_INTROSPECTION_TARGET_PATH', 'app'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Default database connection to use for introspection.
    | null means use the default Laravel connection.
    |
    */

    'connection' => env('DB_INTROSPECTION_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Tables to Ignore by Default
    |--------------------------------------------------------------------------
    |
    | Tables that should be automatically excluded from model generation.
    | Add your custom system tables here.
    |
    */

    'ignore_tables' => [
        'migrations',
        'password_resets',
        'password_reset_tokens',
        'failed_jobs',
        'personal_access_tokens',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'sessions',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'pulse_entries',
        'pulse_aggregates',
        'pulse_values',
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Patterns to Ignore
    |--------------------------------------------------------------------------
    |
    | Regex patterns for tables to ignore. Useful for temporary tables,
    | log tables, or vendor-specific tables.
    |
    */

    'ignore_table_patterns' => [
        '/^temp_/',           // Tables starting with 'temp_'
        '/^backup_/',         // Tables starting with 'backup_'
        '/^_.*/',             // Tables starting with underscore
        // '/.*_log$/',       // Uncomment to ignore tables ending with '_log'
    ],

    /*
    |--------------------------------------------------------------------------
    | PHPDoc Generation
    |--------------------------------------------------------------------------
    |
    | Whether to generate PHPDoc blocks for properties and methods.
    | Highly recommended for IDE autocomplete support.
    |
    */

    'with_phpdoc' => env('DB_INTROSPECTION_WITH_PHPDOC', true),

    /*
    |--------------------------------------------------------------------------
    | Inverse Relationships
    |--------------------------------------------------------------------------
    |
    | Whether to generate inverse relationships (hasMany, hasOne).
    | Set to false if you prefer to define these manually.
    |
    */

    'with_inverse' => env('DB_INTROSPECTION_WITH_INVERSE', true),

    /*
    |--------------------------------------------------------------------------
    | Backup Existing Models
    |--------------------------------------------------------------------------
    |
    | Automatically create backups when overwriting existing models.
    | Backups are saved with .backup.TIMESTAMP extension.
    |
    */

    'backup_existing' => env('DB_INTROSPECTION_BACKUP', false),

    /*
    |--------------------------------------------------------------------------
    | Force Overwrite Without Prompt
    |--------------------------------------------------------------------------
    |
    | If true, automatically overwrite existing models without asking.
    | Use with caution in production environments.
    |
    */

    'force_overwrite' => env('DB_INTROSPECTION_FORCE', false),

    /*
    |--------------------------------------------------------------------------
    | Dry Run Mode
    |--------------------------------------------------------------------------
    |
    | If true, preview changes without writing files.
    | Useful for testing configuration changes.
    |
    */

    'dry_run' => env('DB_INTROSPECTION_DRY_RUN', false),

    /*
    |--------------------------------------------------------------------------
    | Hidden Fields Detection
    |--------------------------------------------------------------------------
    |
    | Patterns for fields that should be automatically hidden in arrays/JSON.
    | Uses case-insensitive matching.
    |
    */

    'hidden_field_patterns' => [
        'password',
        'secret',
        'token',
        'api_key',
        'api_secret',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes Detection
    |--------------------------------------------------------------------------
    |
    | Automatically detect and add SoftDeletes trait when deleted_at column
    | exists in the table.
    |
    */

    'detect_soft_deletes' => env('DB_INTROSPECTION_SOFT_DELETES', true),

    /*
    |--------------------------------------------------------------------------
    | Timestamp Detection
    |--------------------------------------------------------------------------
    |
    | Automatically detect timestamps (created_at, updated_at) and set
    | $timestamps property accordingly.
    |
    */

    'detect_timestamps' => env('DB_INTROSPECTION_TIMESTAMPS', true),

    /*
    |--------------------------------------------------------------------------
    | Cast Inference
    |--------------------------------------------------------------------------
    |
    | Automatically infer casts from database column types.
    | Improves type safety and attribute handling.
    |
    */

    'infer_casts' => env('DB_INTROSPECTION_INFER_CASTS', true),

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | Date format to use for timestamp columns. null uses Laravel default.
    | Example: 'Y-m-d H:i:s'
    |
    */

    'date_format' => env('DB_INTROSPECTION_DATE_FORMAT', null),

    /*
    |--------------------------------------------------------------------------
    | Model Template Path
    |--------------------------------------------------------------------------
    |
    | Path to custom model template stub file (optional).
    | Leave null to use default template. Must be absolute path or
    | relative to project base path.
    |
    */

    'template_path' => env('DB_INTROSPECTION_TEMPLATE', null),

    /*
    |--------------------------------------------------------------------------
    | Model Base Class
    |--------------------------------------------------------------------------
    |
    | The base class that generated models should extend.
    | Default is Illuminate\Database\Eloquent\Model
    |
    */

    'base_model_class' => env('DB_INTROSPECTION_BASE_MODEL', 'Illuminate\\Database\\Eloquent\\Model'),

    /*
    |--------------------------------------------------------------------------
    | Generate Factories
    |--------------------------------------------------------------------------
    |
    | Whether to generate model factories alongside models.
    | Requires Laravel 8+ factory pattern.
    |
    */

    'generate_factories' => env('DB_INTROSPECTION_FACTORIES', false),

    /*
    |--------------------------------------------------------------------------
    | Generate Migrations
    |--------------------------------------------------------------------------
    |
    | Whether to generate migration files from existing database schema.
    | Useful for version controlling database structure.
    |
    */

    'generate_migrations' => env('DB_INTROSPECTION_MIGRATIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Relationship Detection Settings
    |--------------------------------------------------------------------------
    |
    | Fine-tune automatic relationship detection behavior.
    |
    */

    'relationships' => [
        
        // Detect belongsTo from foreign keys
        'detect_belongs_to' => env('DB_INTROSPECTION_BELONGS_TO', true),

        // Detect hasMany/hasOne inverse relationships
        'detect_has_many' => env('DB_INTROSPECTION_HAS_MANY', true),

        // Detect many-to-many relationships from pivot tables
        'detect_many_to_many' => env('DB_INTROSPECTION_MANY_TO_MANY', true),

        // Detect polymorphic relationships (*_type, *_id pairs)
        'detect_polymorphic' => env('DB_INTROSPECTION_POLYMORPHIC', true),

        // Validate foreign key references (warns about broken references)
        'validate_foreign_keys' => env('DB_INTROSPECTION_VALIDATE_FK', false),

        // Automatically determine hasOne vs hasMany based on unique indexes
        'smart_inverse_detection' => env('DB_INTROSPECTION_SMART_INVERSE', true),

        // Include relationship return types in PHPDoc
        'typed_relationships' => env('DB_INTROSPECTION_TYPED_RELATIONS', true),

        // Maximum depth for recursive relationship detection
        'max_relationship_depth' => env('DB_INTROSPECTION_MAX_DEPTH', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Naming Conventions
    |--------------------------------------------------------------------------
    |
    | Customize naming conventions for generated code.
    | Following Laravel conventions is recommended.
    |
    */

    'naming' => [
        
        // Convert table names to singular for model names
        'singular_models' => true,

        // Use StudlyCase for model names (e.g., BlogPost)
        'studly_case_models' => true,

        // Use camelCase for relationship method names
        'camel_case_relationships' => true,

        // Use camelCase for attribute accessor/mutator methods
        'camel_case_accessors' => true,

        // Prefix for pivot model names
        'pivot_model_prefix' => '',

        // Suffix for pivot model names
        'pivot_model_suffix' => 'Pivot',

        // Custom model name mappings (table => model name)
        'custom_model_names' => [
            // 'user_data' => 'User',
            // 'product_info' => 'Product',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fillable and Guarded Settings
    |--------------------------------------------------------------------------
    |
    | Control how fillable and guarded properties are generated.
    |
    */

    'fillable' => [
        
        // Generation strategy: 'auto', 'all', 'none', 'blacklist'
        'strategy' => env('DB_INTROSPECTION_FILLABLE_STRATEGY', 'auto'),

        // Columns to always exclude from fillable
        'exclude' => [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ],

        // Use $guarded instead of $fillable when all but few columns are fillable
        'use_guarded_when_efficient' => true,

        // Threshold: if fillable columns > total * threshold, use $guarded
        'guarded_threshold' => 0.8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Style and Formatting
    |--------------------------------------------------------------------------
    |
    | Code style preferences for generated models.
    |
    */

    'code_style' => [
        
        // Number of spaces for indentation
        'indent_spaces' => 4,

        // Use short array syntax [] instead of array()
        'short_array_syntax' => true,

        // Add trailing commas in multi-line arrays
        'trailing_commas' => true,

        // Line length for wrapping (0 = no limit)
        'line_length' => 120,

        // Add blank line between method definitions
        'blank_line_between_methods' => true,

        // Add blank line before return statements
        'blank_line_before_return' => false,

        // Sort properties alphabetically
        'sort_properties' => false,

        // Sort methods alphabetically (relationships first)
        'sort_methods' => false,

        // Property visibility order (e.g., ['protected', 'public', 'private'])
        'property_visibility_order' => ['protected', 'public', 'private'],

        // Add strict types declaration
        'strict_types' => env('DB_INTROSPECTION_STRICT_TYPES', false),

        // PSR-12 compliance mode
        'psr12_compliance' => env('DB_INTROSPECTION_PSR12', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Settings to optimize generation performance for large databases.
    |
    */

    'performance' => [
        
        // Cache database schema for subsequent runs (in seconds, 0 = disabled)
        'cache_schema' => env('DB_INTROSPECTION_CACHE', 0),

        // Process tables in parallel (requires pcntl extension)
        'parallel_processing' => env('DB_INTROSPECTION_PARALLEL', false),

        // Number of parallel workers (null = auto-detect)
        'parallel_workers' => env('DB_INTROSPECTION_WORKERS', null),

        // Batch size for processing large table lists
        'batch_size' => 50,

        // Memory limit for generation process (e.g., '512M', null = no limit)
        'memory_limit' => env('DB_INTROSPECTION_MEMORY', '512M'),

        // Timeout for database queries in seconds
        'query_timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Features
    |--------------------------------------------------------------------------
    |
    | Additional features for enhanced functionality.
    |
    */

    'advanced' => [
        
        // Generate model events (creating, created, updating, etc.)
        'generate_events' => false,

        // Generate model observers
        'generate_observers' => false,

        // Generate custom collection classes
        'generate_collections' => false,

        // Generate resource classes for API
        'generate_resources' => false,

        // Generate form request validation classes
        'generate_requests' => false,

        // Add scope methods for common queries
        'generate_scopes' => false,

        // Generate trait for shared model behavior
        'generate_traits' => false,

        // Use attributes for model properties (PHP 8+)
        'use_attributes' => false,

        // Generate Enum classes for enum columns (PHP 8.1+)
        'generate_enums' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation and Safety
    |--------------------------------------------------------------------------
    |
    | Safety checks and validation settings.
    |
    */

    'validation' => [
        
        // Validate model names are valid PHP class names
        'validate_class_names' => true,

        // Check for existing files before generation
        'check_existing_files' => true,

        // Verify database connection before starting
        'verify_connection' => true,

        // Warn about tables without primary keys
        'warn_no_primary_key' => true,

        // Warn about tables without foreign keys
        'warn_no_foreign_keys' => false,

        // Require confirmation for large numbers of tables
        'confirm_threshold' => 50,

        // Skip tables with invalid structures
        'skip_invalid_tables' => true,

        // Maximum table name length
        'max_table_name_length' => 64,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Output
    |--------------------------------------------------------------------------
    |
    | Control console output and logging behavior.
    |
    */

    'output' => [
        
        // Verbosity level: 'quiet', 'normal', 'verbose', 'debug'
        'verbosity' => env('DB_INTROSPECTION_VERBOSITY', 'normal'),

        // Show progress bar during generation
        'show_progress' => true,

        // Display summary table after generation
        'show_summary' => true,

        // Show detailed information about each generated model
        'show_details' => false,

        // Log generation to file
        'log_to_file' => env('DB_INTROSPECTION_LOG', false),

        // Log file path (relative to storage/logs)
        'log_file' => 'db-introspection.log',

        // Include generation timestamp in output
        'show_timestamp' => true,

        // Colorize console output
        'colorize_output' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Type Mappings
    |--------------------------------------------------------------------------
    |
    | Override default database type to PHP type mappings.
    | Useful for custom database types or extensions.
    |
    */

    'type_mappings' => [
        // 'geometry' => 'string',
        // 'point' => 'array',
        // 'polygon' => 'array',
        // 'inet' => 'string',
        // 'cidr' => 'string',
        // 'macaddr' => 'string',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Cast Mappings
    |--------------------------------------------------------------------------
    |
    | Override default database type to Laravel cast mappings.
    |
    */

    'cast_mappings' => [
        // 'geometry' => 'string',
        // 'point' => 'array',
        // 'inet' => 'string',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hooks and Callbacks
    |--------------------------------------------------------------------------
    |
    | Custom callbacks for extending generation behavior.
    | Provide fully qualified class names that implement respective interfaces.
    |
    */

    'hooks' => [
        
        // Called before model generation starts
        'before_generation' => null,

        // Called after each model is generated
        'after_model_generated' => null,

        // Called after all models are generated
        'after_generation' => null,

        // Custom model content transformer
        'content_transformer' => null,

        // Custom file name resolver
        'filename_resolver' => null,
    ],
];