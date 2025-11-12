<?php

namespace Zuqongtech\LaravelDbIntrospection\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateModelsFromDatabase extends Command
{
    protected $signature = 'db:generate-models
                            {--namespace=App\\Models : Namespace for generated models}
                            {--connection= : Database connection name (optional)}
                            {--tables=* : Specific tables to generate (optional)}
                            {--force : Overwrite existing models}
                            {--with-phpdoc : Add PHPDoc blocks for IDE support}
                            {--with-inverse : Generate inverse relationships (hasMany, hasOne)}';

    protected $description = 'Introspect any supported database (MySQL, PostgreSQL, SQL Server, SQLite) and generate Eloquent models automatically.';

    protected Filesystem $files;
    protected string $connectionName;
    protected $connection;
    protected string $driver;
    protected array $allTables = [];
    protected array $allForeignKeys = [];

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle(): int
    {
        $this->connectionName = $this->option('connection') ?: config('database.default');
        $this->connection = DB::connection($this->connectionName);
        $this->driver = $this->connection->getDriverName();
        $database = $this->connection->getDatabaseName();

        $this->info("🔍 Inspecting connection [{$this->connectionName}] using driver [{$this->driver}] on database [{$database}]...");

        $this->allTables = $this->getAllTables();

        // Filter tables if specific ones are requested
        $requestedTables = $this->option('tables');
        if (!empty($requestedTables)) {
            $tablesToProcess = array_intersect($this->allTables, $requestedTables);
            if (empty($tablesToProcess)) {
                $this->error('❌ No matching tables found.');
                return 1;
            }
        } else {
            $tablesToProcess = $this->allTables;
        }

        if (empty($tablesToProcess)) {
            $this->warn('⚠️  No tables found in the database.');
            return 0;
        }

        // Pre-load all foreign keys if inverse relationships are requested
        if ($this->option('with-inverse')) {
            $this->info("📊 Loading foreign key relationships...");
            foreach ($this->allTables as $table) {
                $this->allForeignKeys[$table] = $this->getForeignKeys($table);
            }
        }

        $this->info("Found " . count($tablesToProcess) . " table(s) to process.\n");

        foreach ($tablesToProcess as $table) {
            try {
                $this->generateModel($table);
            } catch (\Exception $e) {
                $this->error("❌ Failed to generate model for table '{$table}': {$e->getMessage()}");
            }
        }

        $this->info("\n✅ All models have been generated successfully!");
        return 0;
    }

    protected function getAllTables(): array
    {
        $tables = match ($this->driver) {
            'mysql' => collect($this->connection->select('SHOW TABLES'))
                ->map(fn($t) => array_values((array)$t)[0])
                ->toArray(),

            'pgsql' => collect($this->connection->select("SELECT tablename FROM pg_tables WHERE schemaname='public'"))
                ->pluck('tablename')
                ->toArray(),

            'sqlite' => collect($this->connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')
                ->toArray(),

            'sqlsrv' => collect($this->connection->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'"))
                ->pluck('TABLE_NAME')
                ->toArray(),

            default => throw new \Exception("Unsupported database driver: {$this->driver}")
        };

        // Filter out Laravel migration tables
        return array_values(array_filter($tables, fn($table) => !in_array($table, [
            'migrations',
            'password_resets',
            'password_reset_tokens',
            'failed_jobs',
            'personal_access_tokens'
        ])));
    }

    protected function generateModel(string $table): void
    {
        $modelName = Str::studly(Str::singular($table));

        // Validate model name
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $modelName)) {
            $this->warn("⚠️  Skipping table '{$table}': Invalid model name '{$modelName}'");
            return;
        }

        $namespace = rtrim($this->option('namespace'), '\\');

        // Fix: Properly convert namespace to path
        // App\Models => Models
        // App\Domain\Models => Domain/Models
        $namespacePath = str_replace('\\', '/', $namespace);
        $namespacePath = preg_replace('/^App\//i', '', $namespacePath);

        $modelPath = app_path($namespacePath . '/' . $modelName . '.php');

        if ($this->files->exists($modelPath) && !$this->option('force')) {
            $this->warn("⚠️  Model {$modelName} already exists. Use --force to overwrite.");
            return;
        }

        $columns = Schema::connection($this->connectionName)->getColumnListing($table);
        $columnMeta = $this->getColumnMeta($table);
        $primaryKey = $this->getPrimaryKey($table);
        $foreignKeys = $this->getForeignKeys($table);

        $fillable = $this->generateFillable($columns);
        $hidden = $this->generateHidden($columns);
        $casts = $this->generateCasts($columnMeta);
        $hasTimestamps = $this->hasTimestamps($columns);
        $usesSoftDeletes = in_array('deleted_at', $columns);

        // Generate relationships
        $belongsToRelations = $this->generateBelongsToRelations($foreignKeys);
        $inverseRelations = $this->option('with-inverse') ? $this->generateInverseRelations($table) : '';
        $relations = trim($belongsToRelations . "\n\n" . $inverseRelations);

        $phpDoc = $this->option('with-phpdoc') ? $this->generatePhpDoc($columnMeta, $foreignKeys, $table) : '';

        $template = $this->generateModelTemplate(
            $namespace,
            $modelName,
            $table,
            $primaryKey,
            $fillable,
            $hidden,
            $casts,
            $hasTimestamps,
            $usesSoftDeletes,
            $relations,
            $phpDoc
        );

        // Ensure directory exists
        $directory = dirname($modelPath);
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($modelPath, $template);

        $this->info("✅ Generated: {$modelName} → {$modelPath}");
    }

    protected function getColumnMeta(string $table): array
    {
        return match ($this->driver) {
            'mysql' => $this->connection->select("SHOW COLUMNS FROM `{$table}`"),

            'pgsql' => $this->connection->select("
                SELECT column_name, data_type, is_nullable, column_default, udt_name
                FROM information_schema.columns
                WHERE table_name = ?
                ORDER BY ordinal_position
            ", [$table]),

            'sqlite' => $this->connection->select("PRAGMA table_info(`{$table}`)"),

            'sqlsrv' => $this->connection->select("
                SELECT
                    COLUMN_NAME as column_name,
                    DATA_TYPE as data_type,
                    IS_NULLABLE as is_nullable,
                    COLUMN_DEFAULT as column_default
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ", [$table]),

            default => [],
        };
    }

    protected function getPrimaryKey(string $table): ?string
    {
        try {
            $result = match ($this->driver) {
                'mysql' => collect($this->connection->select("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'"))
                    ->pluck('Column_name')
                    ->first(),

                'pgsql' => $this->connection->selectOne("
                    SELECT a.attname
                    FROM pg_index i
                    JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                    WHERE i.indrelid = ?::regclass AND i.indisprimary
                ", [$table])->attname ?? null,

                'sqlite' => collect($this->connection->select("PRAGMA table_info(`{$table}`)"))
                    ->where('pk', 1)
                    ->pluck('name')
                    ->first(),

                'sqlsrv' => $this->connection->selectOne("
                    SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + '.' + CONSTRAINT_NAME), 'IsPrimaryKey') = 1
                    AND TABLE_NAME = ?
                ", [$table])->COLUMN_NAME ?? null,

                default => null,
            };

            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getForeignKeys(string $table): array
    {
        $foreignKeys = match ($this->driver) {
            'mysql' => $this->connection->select("
                SELECT
                    column_name,
                    referenced_table_name,
                    referenced_column_name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND referenced_table_name IS NOT NULL
            ", [$table]),

            'pgsql' => $this->connection->select("
                SELECT
                    kcu.column_name,
                    ccu.table_name AS referenced_table_name,
                    ccu.column_name AS referenced_column_name
                FROM
                    information_schema.table_constraints AS tc
                    JOIN information_schema.key_column_usage AS kcu
                      ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                    JOIN information_schema.constraint_column_usage AS ccu
                      ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name = ?
            ", [$table]),

            'sqlite' => collect($this->connection->select("PRAGMA foreign_key_list(`{$table}`)"))
                ->map(fn($fk) => (object)[
                    'column_name' => $fk->from,
                    'referenced_table_name' => $fk->table,
                    'referenced_column_name' => $fk->to,
                ])
                ->toArray(),

            'sqlsrv' => $this->connection->select("
                SELECT
                    fkc.COLUMN_NAME AS column_name,
                    pk.TABLE_NAME AS referenced_table_name,
                    pkc.COLUMN_NAME AS referenced_column_name
                FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS rc
                JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS fk ON rc.CONSTRAINT_NAME = fk.CONSTRAINT_NAME
                JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS pk ON rc.UNIQUE_CONSTRAINT_NAME = pk.CONSTRAINT_NAME
                JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS fkc ON rc.CONSTRAINT_NAME = fkc.CONSTRAINT_NAME
                JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS pkc ON pk.CONSTRAINT_NAME = pkc.CONSTRAINT_NAME
                WHERE fk.TABLE_NAME = ?
            ", [$table]),

            default => [],
        };

        return is_array($foreignKeys) ? $foreignKeys : [];
    }

    protected function generateFillable(array $columns): array
    {
        return array_filter($columns, fn($col) => !in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at']));
    }

    protected function generateHidden(array $columns): array
    {
        $sensitivePatterns = ['password', 'secret', 'token', 'api_key', 'remember_token'];

        return array_filter($columns, function ($col) use ($sensitivePatterns) {
            foreach ($sensitivePatterns as $pattern) {
                if (str_contains(strtolower($col), $pattern)) {
                    return true;
                }
            }
            return false;
        });
    }

    protected function hasTimestamps(array $columns): bool
    {
        return in_array('created_at', $columns) && in_array('updated_at', $columns);
    }

    protected function generateCasts(array $columns): array
    {
        $casts = [];

        foreach ($columns as $col) {
            $name = $col->Field ?? $col->column_name ?? $col->name ?? null;
            $type = $col->Type ?? $col->data_type ?? $col->type ?? $col->udt_name ?? '';

            if (!$name) continue;

            // Skip timestamp columns (handled by Laravel)
            if (in_array($name, ['created_at', 'updated_at', 'deleted_at', 'email_verified_at'])) {
                continue;
            }

            $type = strtolower($type);

            if (str_contains($type, 'json') || str_contains($type, 'jsonb')) {
                $casts[$name] = 'array';
            } elseif (str_contains($type, 'bool') || $type === 'tinyint(1)') {
                $casts[$name] = 'boolean';
            } elseif (str_contains($type, 'int')) {
                $casts[$name] = 'integer';
            } elseif (str_contains($type, 'decimal')) {
                // Extract precision if available
                preg_match('/decimal\((\d+),(\d+)\)/', $type, $matches);
                $precision = $matches[2] ?? '2';
                $casts[$name] = "decimal:{$precision}";
            } elseif (in_array($type, ['float', 'double', 'real', 'numeric', 'money'])) {
                $casts[$name] = 'decimal:2';
            } elseif ($type === 'date') {
                $casts[$name] = 'date';
            } elseif (in_array($type, ['datetime', 'timestamp', 'timestamptz'])) {
                $casts[$name] = 'datetime';
            }
        }

        return $casts;
    }

    protected function generateBelongsToRelations(array $foreignKeys): string
    {
        $relations = [];
        $usedNames = [];

        foreach ($foreignKeys as $fk) {
            $column = $fk->column_name ?? $fk->from ?? null;
            $relatedTable = $fk->referenced_table_name ?? $fk->table ?? null;

            if (!$column || !$relatedTable) continue;

            $relatedModel = Str::studly(Str::singular($relatedTable));
            $methodName = Str::camel(str_replace('_id', '', $column));

            // Handle name collisions
            $originalMethodName = $methodName;
            $counter = 1;
            while (in_array($methodName, $usedNames)) {
                $methodName = $originalMethodName . $counter;
                $counter++;
            }
            $usedNames[] = $methodName;

            $relations[] = <<<PHP
    /**
     * Get the {$relatedModel} that owns this record.
     */
    public function {$methodName}()
    {
        return \$this->belongsTo({$relatedModel}::class, '{$column}');
    }
PHP;
        }

        return implode("\n\n", $relations);
    }

    protected function generateInverseRelations(string $currentTable): string
    {
        $relations = [];
        $usedNames = [];

        // Find all tables that reference the current table
        foreach ($this->allForeignKeys as $table => $foreignKeys) {
            if ($table === $currentTable) continue;

            foreach ($foreignKeys as $fk) {
                $referencedTable = $fk->referenced_table_name ?? $fk->table ?? null;

                if ($referencedTable === $currentTable) {
                    $relatedModel = Str::studly(Str::singular($table));
                    $methodName = Str::camel(Str::plural($table));

                    // Handle name collisions
                    $originalMethodName = $methodName;
                    $counter = 1;
                    while (in_array($methodName, $usedNames)) {
                        $methodName = $originalMethodName . $counter;
                        $counter++;
                    }
                    $usedNames[] = $methodName;

                    $foreignKeyColumn = $fk->column_name ?? $fk->from ?? null;

                    $relations[] = <<<PHP
    /**
     * Get all {$relatedModel} records for this record.
     */
    public function {$methodName}()
    {
        return \$this->hasMany({$relatedModel}::class, '{$foreignKeyColumn}');
    }
PHP;
                }
            }
        }

        return implode("\n\n", $relations);
    }

    protected function generatePhpDoc(array $columnMeta, array $foreignKeys, string $currentTable): string
    {
        $properties = [];

        // Add column properties
        foreach ($columnMeta as $col) {
            $name = $col->Field ?? $col->column_name ?? $col->name ?? null;
            $type = $col->Type ?? $col->data_type ?? $col->type ?? '';

            if (!$name) continue;

            $phpType = $this->mapTypeToPhp($type);
            $nullable = ($col->Null ?? $col->is_nullable ?? 'NO') === 'YES' ? '|null' : '';
            $properties[] = " * @property {$phpType}{$nullable} \${$name}";
        }

        // Add belongsTo relationship properties
        foreach ($foreignKeys as $fk) {
            $column = $fk->column_name ?? $fk->from ?? null;
            $relatedTable = $fk->referenced_table_name ?? $fk->table ?? null;

            if (!$column || !$relatedTable) continue;

            $relatedModel = Str::studly(Str::singular($relatedTable));
            $methodName = Str::camel(str_replace('_id', '', $column));
            $properties[] = " * @property-read {$relatedModel}|null \${$methodName}";
        }

        // Add inverse relationship properties
        if ($this->option('with-inverse')) {
            foreach ($this->allForeignKeys as $table => $fks) {
                if ($table === $currentTable) continue;

                foreach ($fks as $fk) {
                    $referencedTable = $fk->referenced_table_name ?? $fk->table ?? null;

                    if ($referencedTable === $currentTable) {
                        $relatedModel = Str::studly(Str::singular($table));
                        $methodName = Str::camel(Str::plural($table));
                        $properties[] = " * @property-read \Illuminate\Database\Eloquent\Collection<{$relatedModel}> \${$methodName}";
                    }
                }
            }
        }

        return "/**\n" . implode("\n", $properties) . "\n */";
    }

    protected function mapTypeToPhp(string $type): string
    {
        $type = strtolower($type);

        if (str_contains($type, 'int')) return 'int';
        if (str_contains($type, 'bool') || $type === 'tinyint(1)') return 'bool';
        if (str_contains($type, 'float') || str_contains($type, 'double') || str_contains($type, 'decimal')) return 'float';
        if (str_contains($type, 'json')) return 'array';
        if (str_contains($type, 'date') || str_contains($type, 'time')) return '\Illuminate\Support\Carbon';

        return 'string';
    }

    protected function generateModelTemplate(
        string $namespace,
        string $modelName,
        string $table,
        ?string $primaryKey,
        array $fillable,
        array $hidden,
        array $casts,
        bool $hasTimestamps,
        bool $usesSoftDeletes,
        string $relations,
        string $phpDoc
    ): string {
        $uses = ["use Illuminate\Database\Eloquent\Model;"];

        if ($usesSoftDeletes) {
            $uses[] = "use Illuminate\Database\Eloquent\SoftDeletes;";
        }

        $usesStr = implode("\n", $uses);
        $traits = $usesSoftDeletes ? "\n    use SoftDeletes;\n" : '';

        $fillableStr = count($fillable) > 0
            ? "\n    protected \$fillable = [" . implode(', ', array_map(fn($col) => "'{$col}'", $fillable)) . "];"
            : "\n    protected \$fillable = [];";

        $hiddenStr = !empty($hidden)
            ? "\n\n    protected \$hidden = [" . implode(', ', array_map(fn($col) => "'{$col}'", $hidden)) . "];"
            : '';

        $castsStr = '';
        if (count($casts)) {
            $castsArray = [];
            foreach ($casts as $col => $type) {
                $castsArray[] = "        '{$col}' => '{$type}',";
            }
            $castsStr = "\n\n    protected \$casts = [\n" . implode("\n", $castsArray) . "\n    ];";
        }

        $timestampsStr = !$hasTimestamps ? "\n\n    public \$timestamps = false;" : '';
        $primaryKeyStr = ($primaryKey && $primaryKey !== 'id') ? "\n    protected \$primaryKey = '{$primaryKey}';" : '';

        $relationsSection = $relations ? "\n\n    // Relationships\n\n{$relations}" : '';
        $phpDocSection = $phpDoc ? "{$phpDoc}\n" : '';

        return <<<PHP
<?php

namespace {$namespace};

{$usesStr}

{$phpDocSection}class {$modelName} extends Model
{{$traits}
    protected \$table = '{$table}';{$primaryKeyStr}{$timestampsStr}
{$fillableStr}{$hiddenStr}{$castsStr}{$relationsSection}
}

PHP;
    }
}
