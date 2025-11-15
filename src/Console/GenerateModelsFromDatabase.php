<?php

namespace Zuqongtech\LaravelDbIntrospection\Console;

use Illuminate\Console\Command;
use Zuqongtech\LaravelDbIntrospection\Support\ConfigValidator;
use Zuqongtech\LaravelDbIntrospection\Support\DatabaseInspector;
use Zuqongtech\LaravelDbIntrospection\Support\RelationshipDetector;
use Zuqongtech\LaravelDbIntrospection\Support\ConstraintAnalyzer;
use Zuqongtech\LaravelDbIntrospection\Support\ModelBuilder;
use Zuqongtech\LaravelDbIntrospection\Support\FileWriter;
use Zuqongtech\LaravelDbIntrospection\Support\Helpers;

class GenerateModelsFromDatabase extends Command
{
    protected $signature = 'zt:generate-models
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
                            {--with-constraints : Include constraint information in model comments}
                            {--validate-fk : Validate foreign key references}
                            {--analyze-constraints : Analyze and display constraint information}
                            {--show-recommendations : Show optimization recommendations}';

    protected $description = 'Introspect database and generate Eloquent models with comprehensive constraint analysis';

    protected DatabaseInspector $inspector;
    protected RelationshipDetector $relationshipDetector;
    protected ConstraintAnalyzer $constraintAnalyzer;
    protected FileWriter $fileWriter;

    public function handle(): int
    {
        $this->info("🔧 Validating configuration...");
        $validator = new ConfigValidator();
        $isValid = $validator->validate();

        if (!$isValid) {
            $this->error("\n❌ Configuration validation failed with the following errors:\n");
            foreach ($validator->getFormattedErrors() as $error) {
                $this->line("  - {$error}");
            }

            if ($validator->hasWarnings()) {
                $this->warn("\n⚠️  Warnings:");
                foreach ($validator->getFormattedWarnings() as $warning) {
                    $this->line("  - {$warning}");
                }
            }

            $this->newLine();
            $this->error("Aborting: please fix configuration issues in config/zt-introspection.php");
            return Command::FAILURE;
        }

        if ($validator->hasWarnings()) {
            $this->warn("⚠️  Configuration warnings detected:");
            foreach ($validator->getFormattedWarnings() as $warning) {
                $this->line("  - {$warning}");
            }
            $this->newLine();
        }

        $this->info("✅ Configuration is valid.\n");

        $connectionName = $this->option('connection') ?: config('database.default');
        $this->inspector = new DatabaseInspector($connectionName);
        $this->relationshipDetector = new RelationshipDetector($this->inspector);
        $this->constraintAnalyzer = new ConstraintAnalyzer($this->inspector);
        
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
            config('zt-introspection.ignore_tables', []),
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

        // Analyze constraints if requested
        if ($this->option('analyze-constraints')) {
            $this->analyzeConstraints($tablesToProcess);
        }

        // Validate constraint integrity
        if ($this->option('validate-fk')) {
            $this->validateConstraintIntegrity($tablesToProcess);
        }

        // Generate models
        $results = [];
        $progressBar = $this->output->createProgressBar(count($tablesToProcess));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        foreach ($tablesToProcess as $table) {
            $progressBar->setMessage("Processing {$table}");
            
            try {
                $result = $this->generateModel($table, $namespace, $basePath);
                $results[] = $result;
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("\n❌ Failed to generate model for table '{$table}': {$e->getMessage()}");
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($results);

        // Show recommendations if requested
        if ($this->option('show-recommendations')) {
            $this->displayRecommendations($tablesToProcess);
        }

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

        // Get comprehensive table metadata
        $metadata = $this->inspector->getTableMetadata($table);
        $columns = $metadata['columns'];
        $foreignKeys = $metadata['foreign_keys'];
        $primaryKey = $metadata['primary_key'];
        $compositePrimaryKey = $metadata['composite_primary_key'];
        $indexes = $metadata['indexes'];
        $uniqueConstraints = $metadata['unique_constraints'];

        // Detect timestamps and soft deletes
        $columnNames = array_column($columns, 'name');
        $hasTimestamps = in_array('created_at', $columnNames) && in_array('updated_at', $columnNames);
        $hasSoftDeletes = in_array('deleted_at', $columnNames);

        // Get constraint analysis
        $constraintAnalysis = null;
        if ($this->option('with-constraints')) {
            $constraintAnalysis = $this->constraintAnalyzer->analyzeTable($table);
        }

        // Build model using ModelBuilder
        $builder = new ModelBuilder($table, $namespace);
        $builder->setColumns($columns)
                ->setForeignKeys($foreignKeys)
                ->setIndexes($indexes)
                ->setUniqueConstraints($uniqueConstraints)
                ->setPrimaryKey($primaryKey)
                ->setCompositePrimaryKey($compositePrimaryKey)
                ->setTimestamps($hasTimestamps)
                ->setSoftDeletes($hasSoftDeletes)
                ->setWithPhpDoc($this->option('with-phpdoc'))
                ->setWithInverse($this->option('with-inverse'))
                ->setWithConstraintComments($this->option('with-constraints'))
                ->setConstraintAnalysis($constraintAnalysis);

        // Add inverse relationships if enabled
        $inverseRelations = [];
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
            'inverse_relationships' => count($inverseRelations),
            'indexes' => count($indexes),
            'unique_constraints' => count($uniqueConstraints),
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

    protected function validateConstraintIntegrity(array $tables): void
    {
        $issues = $this->constraintAnalyzer->validateConstraintIntegrity($tables);
        
        if (!empty($issues)) {
            $this->warn("\n⚠️  Found " . count($issues) . " constraint integrity issue(s):");
            
            foreach ($issues as $issue) {
                $this->line("   - [{$issue['type']}] {$issue['message']}");
            }
            
            $this->newLine();
        } else {
            $this->info("✅ All constraint references are valid");
        }
    }

    protected function analyzeConstraints(array $tables): void
    {
        $this->info("🔍 Analyzing constraints...\n");
        
        $summary = $this->constraintAnalyzer->getConstraintSummary($tables);
        
        $this->info("Constraint Summary:");
        $this->line("  Total Tables: {$summary['total_tables']}");
        $this->line("  Tables with Primary Keys: {$summary['tables_with_pk']}");
        
        if ($summary['tables_without_pk'] > 0) {
            $this->warn("  Tables without Primary Keys: {$summary['tables_without_pk']}");
        }
        
        $this->line("  Total Foreign Keys: {$summary['total_foreign_keys']}");
        $this->line("  Total Indexes: {$summary['total_indexes']}");
        $this->line("  Total Unique Constraints: {$summary['total_unique_constraints']}");
        
        if ($summary['tables_with_issues'] > 0) {
            $this->warn("  Tables with Issues: {$summary['tables_with_issues']}");
        }
        
        $this->newLine();
    }

    protected function displayRecommendations(array $tables): void
    {
        $this->info("💡 Optimization Recommendations:\n");
        
        $hasRecommendations = false;
        
        foreach ($tables as $table) {
            $analysis = $this->constraintAnalyzer->analyzeTable($table);
            
            if (!empty($analysis['recommendations'])) {
                $hasRecommendations = true;
                $this->line("Table: <comment>{$table}</comment>");
                
                foreach ($analysis['recommendations'] as $rec) {
                    $icon = match($rec['type']) {
                        'warning' => '⚠️ ',
                        'performance' => '⚡',
                        'optimization' => '🔧',
                        'info' => 'ℹ️ ',
                        default => '•'
                    };
                    
                    $this->line("  {$icon} [{$rec['category']}] {$rec['message']}");
                    $this->line("     → {$rec['suggestion']}");
                }
                
                $this->newLine();
            }
        }
        
        if (!$hasRecommendations) {
            $this->info("✅ No optimization recommendations - your database structure looks good!");
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
                    $r['indexes'] ?? 0,
                    $r['unique_constraints'] ?? 0,
                    $r['existed'] ? 'Overwritten' : 'Created'
                ])
                ->toArray();
            
            $this->table(
                ['Model', 'Table', 'Columns', 'Relations', 'Indexes', 'Unique', 'Status'],
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