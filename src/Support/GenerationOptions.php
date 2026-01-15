<?php

namespace Zuqongtech\LaravelDbIntrospection\Support;

use Illuminate\Console\Command;

/**
 * Data Transfer Object for generation options
 *
 * This class encapsulates all configuration options for the generation process,
 * normalizing CLI flags and config values into a single, type-safe object.
 */
final class GenerationOptions
{
    /**
     * Create a new GenerationOptions instance
     *
     * @param  bool  $models  Generate Eloquent models
     * @param  bool  $controllers  Generate RESTful controllers
     * @param  bool  $resources  Generate API resources
     * @param  bool  $observers  Generate model observers
     * @param  bool  $policies  Generate authorization policies
     * @param  bool  $force  Overwrite existing files without asking
     * @param  bool  $dryRun  Preview changes without writing files
     * @param  bool  $backup  Backup existing files before overwriting
     * @param  bool  $withPhpDoc  Add PHPDoc blocks for IDE support
     * @param  bool  $withInverse  Generate inverse relationships (hasMany, hasOne)
     * @param  bool  $withConstraints  Include constraint information in model comments
     * @param  bool  $validateFk  Validate foreign key references
     * @param  bool  $analyzeConstraints  Analyze and display constraint information
     * @param  bool  $showRecommendations  Show optimization recommendations
     * @param  string|null  $namespace  Namespace for generated models
     * @param  string|null  $path  Base path for generated files
     * @param  string|null  $connection  Database connection name
     * @param  array  $tables  Specific tables to generate (empty = all tables)
     * @param  array  $ignore  Tables to ignore
     */
    public function __construct(
        public bool $models = true,
        public bool $controllers = false,
        public bool $resources = false,
        public bool $observers = false,
        public bool $policies = false,
        public bool $force = false,
        public bool $dryRun = false,
        public bool $backup = false,
        public bool $withPhpDoc = true,
        public bool $withInverse = true,
        public bool $withConstraints = false,
        public bool $validateFk = false,
        public bool $analyzeConstraints = false,
        public bool $showRecommendations = false,
        public ?string $namespace = null,
        public ?string $path = null,
        public ?string $connection = null,
        public array $tables = [],
        public array $ignore = [],
    ) {}

    /**
     * Create GenerationOptions from Artisan command
     */
    public static function fromCommand(Command $command): self
    {
        $all = $command->option('all') ?? false;

        return new self(
            models: true, // Always generate models
            controllers: $all || ($command->option('controllers') ?? false),
            resources: $all || ($command->option('resources') ?? false),
            observers: $all || ($command->option('observers') ?? false),
            policies: $all || ($command->option('policies') ?? false),
            force: $command->option('force') ?? false,
            dryRun: $command->option('dry-run') ?? false,
            backup: $command->option('backup') ?? false,
            withPhpDoc: $command->option('with-phpdoc') ?? true,
            withInverse: $command->option('with-inverse') ?? true,
            withConstraints: $command->option('with-constraints') ?? false,
            validateFk: $command->option('validate-fk') ?? false,
            analyzeConstraints: $command->option('analyze-constraints') ?? false,
            showRecommendations: $command->option('show-recommendations') ?? false,
            namespace: $command->option('namespace'),
            path: $command->option('path'),
            connection: $command->option('connection'),
            tables: $command->option('tables') ?? [],
            ignore: $command->option('ignore') ?? [],
        );
    }

    /**
     * Create GenerationOptions from array
     */
    public static function fromArray(array $options): self
    {
        return new self(
            models: $options['models'] ?? true,
            controllers: $options['controllers'] ?? false,
            resources: $options['resources'] ?? false,
            observers: $options['observers'] ?? false,
            policies: $options['policies'] ?? false,
            force: $options['force'] ?? false,
            dryRun: $options['dry_run'] ?? false,
            backup: $options['backup'] ?? false,
            withPhpDoc: $options['with_phpdoc'] ?? true,
            withInverse: $options['with_inverse'] ?? true,
            withConstraints: $options['with_constraints'] ?? false,
            validateFk: $options['validate_fk'] ?? false,
            analyzeConstraints: $options['analyze_constraints'] ?? false,
            showRecommendations: $options['show_recommendations'] ?? false,
            namespace: $options['namespace'] ?? null,
            path: $options['path'] ?? null,
            connection: $options['connection'] ?? null,
            tables: $options['tables'] ?? [],
            ignore: $options['ignore'] ?? [],
        );
    }

    /**
     * Create GenerationOptions with defaults from config
     */
    public static function withDefaults(): self
    {
        return new self(
            models: true,
            controllers: false,
            resources: false,
            observers: false,
            policies: false,
            force: config('zt-introspection.force_overwrite', false),
            dryRun: config('zt-introspection.dry_run', false),
            backup: config('zt-introspection.backup_existing', false),
            withPhpDoc: config('zt-introspection.with_phpdoc', true),
            withInverse: config('zt-introspection.with_inverse', true),
            withConstraints: false,
            validateFk: config('zt-introspection.relationships.validate_foreign_keys', false),
            analyzeConstraints: false,
            showRecommendations: false,
            namespace: config('zt-introspection.namespace', 'App\\Models'),
            path: config('zt-introspection.target_path', 'app'),
            connection: config('zt-introspection.connection'),
            tables: [],
            ignore: config('zt-introspection.ignore_tables', []),
        );
    }

    /**
     * Check if any artifact generation is enabled
     */
    public function hasAnyArtifacts(): bool
    {
        return $this->models
            || $this->controllers
            || $this->resources
            || $this->observers
            || $this->policies;
    }

    /**
     * Get list of enabled generators
     */
    public function getEnabledGenerators(): array
    {
        $enabled = [];

        if ($this->models) {
            $enabled[] = 'Models';
        }
        if ($this->controllers) {
            $enabled[] = 'Controllers';
        }
        if ($this->resources) {
            $enabled[] = 'Resources';
        }
        if ($this->observers) {
            $enabled[] = 'Observers';
        }
        if ($this->policies) {
            $enabled[] = 'Policies';
        }

        return $enabled;
    }

    /**
     * Check if only models should be generated
     */
    public function isModelsOnly(): bool
    {
        return $this->models
            && ! $this->controllers
            && ! $this->resources
            && ! $this->observers
            && ! $this->policies;
    }

    /**
     * Get namespace with fallback to config
     */
    public function getNamespace(): string
    {
        return $this->namespace ?? config('zt-introspection.namespace', 'App\\Models');
    }

    /**
     * Get path with fallback to config
     */
    public function getPath(): string
    {
        return $this->path ?? config('zt-introspection.target_path', 'app');
    }

    /**
     * Get connection with fallback to default
     */
    public function getConnection(): string
    {
        return $this->connection ?? config('zt-introspection.connection') ?? config('database.default');
    }

    /**
     * Check if specific tables are requested
     */
    public function hasSpecificTables(): bool
    {
        return ! empty($this->tables);
    }

    /**
     * Check if any tables should be ignored
     */
    public function hasIgnoredTables(): bool
    {
        return ! empty($this->ignore);
    }

    /**
     * Get all ignored tables (including config defaults)
     */
    public function getAllIgnoredTables(): array
    {
        return array_merge(
            config('zt-introspection.ignore_tables', []),
            $this->ignore
        );
    }

    /**
     * Merge with another GenerationOptions instance
     */
    public function merge(GenerationOptions $other): self
    {
        return new self(
            models: $other->models || $this->models,
            controllers: $other->controllers || $this->controllers,
            resources: $other->resources || $this->resources,
            observers: $other->observers || $this->observers,
            policies: $other->policies || $this->policies,
            force: $other->force || $this->force,
            dryRun: $other->dryRun || $this->dryRun,
            backup: $other->backup || $this->backup,
            withPhpDoc: $other->withPhpDoc ?? $this->withPhpDoc,
            withInverse: $other->withInverse ?? $this->withInverse,
            withConstraints: $other->withConstraints || $this->withConstraints,
            validateFk: $other->validateFk || $this->validateFk,
            analyzeConstraints: $other->analyzeConstraints || $this->analyzeConstraints,
            showRecommendations: $other->showRecommendations || $this->showRecommendations,
            namespace: $other->namespace ?? $this->namespace,
            path: $other->path ?? $this->path,
            connection: $other->connection ?? $this->connection,
            tables: array_merge($this->tables, $other->tables),
            ignore: array_merge($this->ignore, $other->ignore),
        );
    }

    /**
     * Create a copy with modified values
     */
    public function with(array $overrides): self
    {
        return new self(
            models: $overrides['models'] ?? $this->models,
            controllers: $overrides['controllers'] ?? $this->controllers,
            resources: $overrides['resources'] ?? $this->resources,
            observers: $overrides['observers'] ?? $this->observers,
            policies: $overrides['policies'] ?? $this->policies,
            force: $overrides['force'] ?? $this->force,
            dryRun: $overrides['dryRun'] ?? $this->dryRun,
            backup: $overrides['backup'] ?? $this->backup,
            withPhpDoc: $overrides['withPhpDoc'] ?? $this->withPhpDoc,
            withInverse: $overrides['withInverse'] ?? $this->withInverse,
            withConstraints: $overrides['withConstraints'] ?? $this->withConstraints,
            validateFk: $overrides['validateFk'] ?? $this->validateFk,
            analyzeConstraints: $overrides['analyzeConstraints'] ?? $this->analyzeConstraints,
            showRecommendations: $overrides['showRecommendations'] ?? $this->showRecommendations,
            namespace: $overrides['namespace'] ?? $this->namespace,
            path: $overrides['path'] ?? $this->path,
            connection: $overrides['connection'] ?? $this->connection,
            tables: $overrides['tables'] ?? $this->tables,
            ignore: $overrides['ignore'] ?? $this->ignore,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'models' => $this->models,
            'controllers' => $this->controllers,
            'resources' => $this->resources,
            'observers' => $this->observers,
            'policies' => $this->policies,
            'force' => $this->force,
            'dry_run' => $this->dryRun,
            'backup' => $this->backup,
            'with_phpdoc' => $this->withPhpDoc,
            'with_inverse' => $this->withInverse,
            'with_constraints' => $this->withConstraints,
            'validate_fk' => $this->validateFk,
            'analyze_constraints' => $this->analyzeConstraints,
            'show_recommendations' => $this->showRecommendations,
            'namespace' => $this->namespace,
            'path' => $this->path,
            'connection' => $this->connection,
            'tables' => $this->tables,
            'ignore' => $this->ignore,
        ];
    }

    /**
     * Get a summary string of enabled options
     */
    public function getSummary(): string
    {
        $parts = [];

        $generators = $this->getEnabledGenerators();
        if (! empty($generators)) {
            $parts[] = 'Generators: '.implode(', ', $generators);
        }

        if ($this->force) {
            $parts[] = 'Force overwrite';
        }

        if ($this->dryRun) {
            $parts[] = 'Dry run mode';
        }

        if ($this->backup) {
            $parts[] = 'Backup enabled';
        }

        if ($this->hasSpecificTables()) {
            $parts[] = 'Tables: '.implode(', ', $this->tables);
        }

        return implode(' | ', $parts);
    }

    /**
     * Validate the options
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        // Check if at least one generator is enabled
        if (! $this->hasAnyArtifacts()) {
            $errors[] = 'At least one generator must be enabled';
        }

        // Validate namespace format
        if ($this->namespace) {
            if (! preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*$/', $this->namespace)) {
                $errors[] = 'Invalid namespace format';
            }
        }

        // Check if path exists or can be created
        if ($this->path) {
            $fullPath = base_path($this->path);
            if (! is_dir($fullPath) && ! @mkdir($fullPath, 0755, true)) {
                $errors[] = "Path does not exist and cannot be created: {$this->path}";
            }
        }

        // Validate connection exists
        if ($this->connection) {
            $connections = array_keys(config('database.connections', []));
            if (! in_array($this->connection, $connections)) {
                $errors[] = "Database connection '{$this->connection}' is not configured";
            }
        }

        // Validate tables are not empty strings
        foreach ($this->tables as $table) {
            if (! is_string($table) || empty(trim($table))) {
                $errors[] = 'Invalid table name in tables list';
                break;
            }
        }

        // Validate ignore list
        foreach ($this->ignore as $table) {
            if (! is_string($table) || empty(trim($table))) {
                $errors[] = 'Invalid table name in ignore list';
                break;
            }
        }

        return $errors;
    }

    /**
     * Check if options are valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Debug representation
     */
    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
