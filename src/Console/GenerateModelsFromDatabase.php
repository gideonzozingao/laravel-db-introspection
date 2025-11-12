<?php

namespace Zuqongtech\LaravelDbIntrospection\Console;

use Illuminate\Console\Command;
use Zuqongtech\LaravelDbIntrospection\Support\DatabaseInspector;
use Zuqongtech\LaravelDbIntrospection\Support\RelationshipDetector;
use Zuqongtech\LaravelDbIntrospection\Support\ModelBuilder;
use Zuqongtech\LaravelDbIntrospection\Support\FileWriter;
use Zuqongtech\LaravelDbIntrospection\Support\Helpers;

class GenerateModelsFromDatabase extends Command
{
    protected $signature = 'introspection:generate-models
                            {--namespace=App\\Models : Namespace for generated models}
                            {--connection= : Database connection name (optional)}
                            {--tables=* : Specific tables to generate (optional)}
                            {--ignore=* : Tables to ignore}
                            {--path=app : Base path for generated models}
                            {--force : Overwrite existing models}
                            {--backup : Backup existing models before overwriting}
                            {--dry-run : Preview changes without writing files}
                            {--with-phpdoc : Add PHPDoc blocks for IDE support}
                            {--with-inverse : Generate inverse relationships (hasMany, hasOne)}
                            {--validate-fk : Validate foreign key references}';

    protected $description = 'Introspect database and generate Eloquent models with relationships';

    protected DatabaseInspector $inspector;
    protected RelationshipDetector $relationshipDetector;
    protected FileWriter $fileWriter;

    public function handle(): int
    {
        // Initialize components
        $connectionName = $this->option('connection') ?: config('database.default');
        $this->inspector = new DatabaseInspector($connectionName);
        $this->relationshipDetector = new RelationshipDetector($this->inspector);
        
        $namespace = $this->option('namespace');
        $basePath = $this->option('path');
        $dryRun = $this->option('dry-run');
        
        $this->fileWriter = new FileWriter(base_path(), $dryRun);

        // Display connection info
        $driver = $this->inspector->getDriver();
        $database = $this->inspector->getDatabaseName();
        
        $this->info("🔍 Inspecting connection [{$connectionName}] using driver [{$driver}] on database [{$database}]...");
        
        if ($dryRun) {
            $this->warn("🔸 DRY RUN MODE - No files will be written");
        }

        // Get all tables
        $allTables = $this->inspector->getAllTables();
        
        // Apply filters
        $ignoreTables = array_merge(
            config('db-introspection.ignore_tables', []),
            $this->option('ignore')
        );
        
        $requestedTables = $this->option('tables');
        
        $tablesToProcess = $this->filterTables($allTables, $requestedTables, $ignoreTables);

        if (empty($tablesToProcess)) {
            $this->warn('⚠️  No tables found to process.');
            return 0;
        }

        $this->info("📊 Found " . count($tablesToProcess) . " table(s) to process.\n");

        // Build foreign key map for relationship detection
        if ($this->option('with-inverse')) {
            $this->info("🔗 Building relationship map...");
            $this->relationshipDetector->buildForeignKeyMap($allTables);
            
            // Validate foreign keys if requested
            if ($this->option('validate-fk')) {
                $this->validateForeignKeys();
            }
        }

        // Generate models
        $results = [];
        $progressBar = $this->output->createProgressBar(count($tablesToProcess));
        $progressBar->start();

        foreach ($tablesToProcess as $table) {
            try {
                $result = $this->generateModel($table, $namespace, $basePath);
                $results[] = $result;
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("\n❌ Failed to generate model for table '{$table}': {$e->getMessage()}");
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($results);

        return 0;
    }

    protected function filterTables(array $allTables, array $requestedTables, array $ignoreTables): array
    {
        // Filter by requested tables
        if (!empty($requestedTables)) {
            $allTables = array_intersect($allTables, $requestedTables);
        }

        // Remove ignored tables
        $filtered = array_filter($allTables, function ($table) use ($ignoreTables) {
            return !Helpers::shouldIgnoreTable($table, $ignoreTables);
        });

        return array_values($filtered);
    }

    protected function generateModel(string $table, string $namespace, string $basePath): array
    {
        $modelName = Helpers::tableToModelName($table);
        
        // Validate model name
        if (!Helpers::isValidClassName($modelName)) {
            throw new \Exception("Invalid model name: {$modelName}");
        }

        // Check if model exists
        $modelExists = $this->fileWriter->modelExists($namespace, $modelName, $basePath);
        
        if ($modelExists && !$this->option('force') && !$this->option('dry-run')) {
            $this->warn("⚠️  Skipping {$modelName}: already exists (use --force to overwrite)");
            return [
                'table' => $table,
                'model' => $modelName,
                'status' => 'skipped',
                'reason' => 'already exists'
            ];
        }

        // Backup existing model if requested
        if ($modelExists && $this->option('backup') && !$this->option('dry-run')) {
            $backupPath = $this->fileWriter->backupModel($namespace, $modelName, $basePath);
            if ($backupPath) {
                $this->line("💾 Backed up to: {$backupPath}");
            }
        }

        // Get table metadata
        $columns = $this->inspector->getColumns($table);
        $foreignKeys = $this->inspector->getForeignKeys($table);
        $primaryKey = $this->inspector->getPrimaryKey($table);
        $indexes = $this->inspector->getIndexes($table);

        // Detect timestamps and soft deletes
        $columnNames = array_column($columns, 'name');
        $hasTimestamps = in_array('created_at', $columnNames) && in_array('updated_at', $columnNames);
        $hasSoftDeletes = in_array('deleted_at', $columnNames);

        // Build model using ModelBuilder
        $builder = new ModelBuilder($table, $namespace);
        $builder->setColumns($columns)
                ->setForeignKeys($foreignKeys)
                ->setPrimaryKey($primaryKey)
                ->setTimestamps($hasTimestamps)
                ->setSoftDeletes($hasSoftDeletes)
                ->setWithPhpDoc($this->option('with-phpdoc'))
                ->setWithInverse($this->option('with-inverse'));

        // Add inverse relationships if enabled
        if ($this->option('with-inverse')) {
            $inverseRelations = $this->relationshipDetector->getInverseRelationships($table);
            foreach ($inverseRelations as $relation) {
                $builder->addInverseRelationship(
                    $relation['method'],
                    $relation['model'],
                    $relation['foreign_key']
                );
            }
        }

        // Build model content
        $modelContent = $builder->build();

        // Write model to file
        $writeResult = $this->fileWriter->writeModel(
            $modelContent,
            $namespace,
            $modelName,
            $basePath
        );

        return [
            'table' => $table,
            'model' => $modelName,
            'status' => $writeResult['written'] ? 'success' : 'failed',
            'path' => $writeResult['relative_path'],
            'existed' => $writeResult['existed'],
            'message' => $writeResult['message'],
            'columns' => count($columns),
            'relationships' => count($foreignKeys),
            'inverse_relationships' => count($inverseRelations ?? []),
        ];
    }

    protected function validateForeignKeys(): void
    {
        $issues = $this->relationshipDetector->validateForeignKeys();
        
        if (!empty($issues)) {
            $this->warn("\n⚠️  Found " . count($issues) . " foreign key issue(s):");
            
            foreach ($issues as $issue) {
                $this->line("   - {$issue['table']}.{$issue['column']}: {$issue['issue']}");
            }
            
            $this->newLine();
        } else {
            $this->info("✅ All foreign key references are valid");
        }
    }

    protected function displaySummary(array $results): void
    {
        $successful = collect($results)->where('status', 'success')->count();
        $skipped = collect($results)->where('status', 'skipped')->count();
        $failed = collect($results)->where('status', 'failed')->count();

        $this->info("📊 Generation Summary:");
        $this->info("   ✅ Successful: {$successful}");
        
        if ($skipped > 0) {
            $this->info("   ⏭️  Skipped: {$skipped}");
        }
        
        if ($failed > 0) {
            $this->error("   ❌ Failed: {$failed}");
        }

        // Display detailed results table
        if ($successful > 0) {
            $this->newLine();
            $this->info("Generated Models:");
            
            $tableData = collect($results)
                ->where('status', 'success')
                ->map(fn($r) => [
                    $r['model'],
                    $r['table'],
                    $r['columns'],
                    $r['relationships'] + ($r['inverse_relationships'] ?? 0),
                    $r['existed'] ? 'Overwritten' : 'Created'
                ])
                ->toArray();
            
            $this->table(
                ['Model', 'Table', 'Columns', 'Relations', 'Status'],
                $tableData
            );
        }

        // Show pivot table detection
        if ($this->option('with-inverse')) {
            $pivotTables = $this->relationshipDetector->getPivotTables();
            
            if (!empty($pivotTables)) {
                $this->newLine();
                $this->info("🔄 Detected Pivot Tables:");
                
                foreach ($pivotTables as $pivot) {
                    $this->line("   {$pivot['pivot_table']}: {$pivot['model1']} ↔ {$pivot['model2']}");
                }
            }
        }

        $this->newLine();
        $this->info("✅ Model generation complete!");
        
        if ($this->option('dry-run')) {
            $this->warn("🔸 This was a dry run - no files were written");
        }
    }
}