<?php

namespace Zuqongtech\LaravelDbIntrospection\Console;

use Illuminate\Console\Command;
use Zuqongtech\LaravelDbIntrospection\Support\ConfigValidator;
use Zuqongtech\LaravelDbIntrospection\Support\ConstraintAnalyzer;
use Zuqongtech\LaravelDbIntrospection\Support\DatabaseInspector;
use Zuqongtech\LaravelDbIntrospection\Support\FileWriter;
use Zuqongtech\LaravelDbIntrospection\Support\GenerationOptions;
use Zuqongtech\LaravelDbIntrospection\Support\GenerationOrchestrator;
use Zuqongtech\LaravelDbIntrospection\Support\Helpers;
use Zuqongtech\LaravelDbIntrospection\Support\ModelBuilder;
use Zuqongtech\LaravelDbIntrospection\Support\ModelMetadata;
use Zuqongtech\LaravelDbIntrospection\Support\RelationshipDetector;

class GenerateModelsFromDatabase extends Command
{
    protected $signature = 'zt:generate
                            {--all : Generate all artifacts (models, controllers, resources, observers, policies)}
                            {--models : Generate models (always enabled)}
                            {--controllers : Generate controllers}
                            {--resources : Generate API resources}
                            {--observers : Generate observers}
                            {--policies : Generate authorization policies}
                            {--namespace=App\\Models : Namespace for generated models}
                            {--connection= : Database connection name (optional)}
                            {--tables=* : Specific tables to generate (optional)}
                            {--ignore=* : Tables to ignore}
                            {--only=* : Generate only for specific tables (alias for --tables)}
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

    protected $description = 'Generate models and related artifacts from database introspection';

    protected DatabaseInspector $inspector;

    protected RelationshipDetector $relationshipDetector;

    protected ConstraintAnalyzer $constraintAnalyzer;

    protected FileWriter $fileWriter;

    protected GenerationOrchestrator $orchestrator;

    public function handle(): int
    {
        $this->info('🔧 Validating configuration...');
        $validator = new ConfigValidator;
        $isValid = $validator->validate();

        if (! $isValid) {
            $this->displayValidationErrors($validator);

            return Command::FAILURE;
        }

        if ($validator->hasWarnings()) {
            $this->displayValidationWarnings($validator);
        }

        $this->info("✅ Configuration is valid.\n");

        // Parse options using GenerationOptions
        $options = GenerationOptions::fromCommand($this);

        // Handle --only alias for --tables
        if ($this->option('only')) {
            $options->tables = array_merge($options->tables, $this->option('only'));
        }

        // Setup components
        $this->setupComponents($options);

        // Display generation plan
        $this->displayGenerationPlan($options);

        // Get all tables
        $allTables = $this->inspector->getAllTables();

        // Apply filters
        $tablesToProcess = $this->filterTables($allTables, $options);

        if (empty($tablesToProcess)) {
            $this->warn('⚠️  No tables found to process.');

            return Command::SUCCESS;
        }

        $this->info('📊 Found '.count($tablesToProcess)." table(s) to process.\n");

        // Build foreign key map for relationship detection
        if ($options->withInverse) {
            $this->info('🔗 Building relationship map...');
            $this->relationshipDetector->buildForeignKeyMap($allTables);

            // Validate foreign keys if requested
            if ($options->validateFk) {
                $this->validateForeignKeys();
            }
        }

        // Analyze constraints if requested
        if ($options->analyzeConstraints) {
            $this->analyzeConstraints($tablesToProcess);
        }

        // Validate constraint integrity
        if ($options->validateFk) {
            $this->validateConstraintIntegrity($tablesToProcess);
        }

        // Generate all artifacts
        $results = $this->generateArtifacts($tablesToProcess, $options);

        // Display summary
        $this->displaySummary($results, $options);

        // Show recommendations if requested
        if ($options->showRecommendations) {
            $this->displayRecommendations($tablesToProcess);
        }

        return Command::SUCCESS;
    }

    protected function setupComponents(GenerationOptions $options): void
    {
        $connectionName = $options->getConnection();
        $this->inspector = new DatabaseInspector($connectionName);
        $this->relationshipDetector = new RelationshipDetector($this->inspector);
        $this->constraintAnalyzer = new ConstraintAnalyzer($this->inspector);
        $this->fileWriter = new FileWriter(base_path(), $options->dryRun);

        // Setup orchestrator with generators
        $this->orchestrator = app(GenerationOrchestrator::class);

        $driver = $this->inspector->getDriver();
        $database = $this->inspector->getDatabaseName();

        $this->info("🔍 Inspecting connection [{$connectionName}] using driver [{$driver}] on database [{$database}]...");

        if ($options->dryRun) {
            $this->warn('🔸 DRY RUN MODE - No files will be written');
        }
    }

    protected function displayGenerationPlan(GenerationOptions $options): void
    {
        $artifacts = $options->getEnabledGenerators();

        if (! empty($artifacts)) {
            $this->info('📋 Generation Plan:');
            $this->line('   Will generate: '.implode(', ', $artifacts));
            $this->newLine();
        }
    }

    protected function filterTables(array $allTables, GenerationOptions $options): array
    {
        // Filter by requested tables
        if ($options->hasSpecificTables()) {
            $allTables = array_intersect($allTables, $options->tables);
        }

        // Remove ignored tables
        $ignoreTables = $options->getAllIgnoredTables();

        $filtered = array_filter($allTables, function ($table) use ($ignoreTables) {
            return ! Helpers::shouldIgnoreTable($table, $ignoreTables);
        });

        return array_values($filtered);
    }

    protected function generateArtifacts(array $tables, GenerationOptions $options): array
    {
        $allResults = [];
        $progressBar = $this->output->createProgressBar(count($tables));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        foreach ($tables as $table) {
            $progressBar->setMessage("Processing {$table}");

            try {
                // Generate model (preserve existing functionality)
                $modelResult = $this->generateModel($table, $options);

                // Build metadata for other generators
                $meta = ModelMetadata::fromTable($table, $this->inspector);

                // Add inverse relationships if enabled
                if ($options->withInverse) {
                    $meta->inverseRelationships = $this->relationshipDetector->getInverseRelationships($table);
                }

                // Add constraint analysis if enabled
                if ($options->withConstraints) {
                    $meta->constraintAnalysis = $this->constraintAnalyzer->analyzeTable($table);
                }

                // Generate other artifacts through orchestrator
                $artifactResults = [];
                if ($options->controllers || $options->resources || $options->observers || $options->policies) {
                    $orchestratorResults = $this->orchestrator->generate([$meta], $options);
                    $artifactResults = $orchestratorResults[0]['artifacts'] ?? [];
                }

                $allResults[] = [
                    'table' => $table,
                    'model' => $modelResult,
                    'artifacts' => $artifactResults,
                ];

                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("\n❌ Failed to process table '{$table}': {$e->getMessage()}");
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        return $allResults;
    }

    protected function generateModel(string $table, GenerationOptions $options): array
    {
        $modelName = Helpers::tableToModelName($table);
        $namespace = $options->getNamespace();
        $basePath = $options->getPath();

        // Validate model name
        if (! Helpers::isValidClassName($modelName)) {
            throw new \Exception("Invalid model name: {$modelName}");
        }

        // Check if model exists
        $modelExists = $this->fileWriter->modelExists($namespace, $modelName, $basePath);

        if ($modelExists && ! $options->force && ! $options->dryRun) {
            return [
                'table' => $table,
                'model' => $modelName,
                'status' => 'skipped',
                'reason' => 'already exists',
            ];
        }

        // Backup existing model if requested
        if ($modelExists && $options->backup && ! $options->dryRun) {
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
        if ($options->withConstraints) {
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
            ->setWithPhpDoc($options->withPhpDoc)
            ->setWithInverse($options->withInverse)
            ->setWithConstraintComments($options->withConstraints)
            ->setConstraintAnalysis($constraintAnalysis);

        // Add inverse relationships if enabled
        $inverseRelations = [];
        if ($options->withInverse) {
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

    protected function displaySummary(array $results, GenerationOptions $options): void
    {
        $this->info('📊 Generation Summary:');

        $modelStats = [
            'success' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $artifactStats = [];

        foreach ($results as $result) {
            // Count model results
            $modelStatus = $result['model']['status'] ?? 'unknown';
            if (isset($modelStats[$modelStatus])) {
                $modelStats[$modelStatus]++;
            }

            // Count artifact results
            foreach ($result['artifacts'] ?? [] as $artifact) {
                $type = $artifact['type'] ?? 'unknown';
                $status = $artifact['status'] ?? 'unknown';

                if (! isset($artifactStats[$type])) {
                    $artifactStats[$type] = ['success' => 0, 'skipped' => 0, 'failed' => 0];
                }

                if (isset($artifactStats[$type][$status])) {
                    $artifactStats[$type][$status]++;
                }
            }
        }

        // Display model summary
        $this->info("\n   Models:");
        $this->info("      ✅ Successful: {$modelStats['success']}");
        if ($modelStats['skipped'] > 0) {
            $this->info("      ⏭️  Skipped: {$modelStats['skipped']}");
        }
        if ($modelStats['failed'] > 0) {
            $this->error("      ❌ Failed: {$modelStats['failed']}");
        }

        // Display artifact summaries
        foreach ($artifactStats as $type => $stats) {
            $this->info("\n   {$type}s:");
            $this->info("      ✅ Successful: {$stats['success']}");
            if ($stats['skipped'] > 0) {
                $this->info("      ⏭️  Skipped: {$stats['skipped']}");
            }
            if ($stats['failed'] > 0) {
                $this->error("      ❌ Failed: {$stats['failed']}");
            }
        }

        // Display detailed results table if successful
        if ($modelStats['success'] > 0) {
            $this->newLine();
            $this->displayDetailedResults($results);
        }

        // Show pivot table detection
        if ($options->withInverse) {
            $pivotTables = $this->relationshipDetector->getPivotTables();

            if (! empty($pivotTables)) {
                $this->newLine();
                $this->info('🔄 Detected Pivot Tables:');

                foreach ($pivotTables as $pivot) {
                    $this->line("   {$pivot['pivot_table']}: {$pivot['model1']} ↔ {$pivot['model2']}");
                }
            }
        }

        $this->newLine();
        $this->info('✅ Generation complete!');

        if ($options->dryRun) {
            $this->warn('🔸 This was a dry run - no files were written');
        }
    }

    protected function displayDetailedResults(array $results): void
    {
        $successfulModels = array_filter($results, fn ($r) => ($r['model']['status'] ?? '') === 'success');

        if (empty($successfulModels)) {
            return;
        }

        $this->info('Generated Artifacts:');

        $tableData = [];
        foreach ($successfulModels as $result) {
            $model = $result['model'];
            $artifacts = $result['artifacts'] ?? [];

            $artifactList = collect($artifacts)
                ->where('status', 'success')
                ->map(fn ($a) => $a['type'])
                ->implode(', ');

            if (empty($artifactList)) {
                $artifactList = 'Model only';
            }

            $tableData[] = [
                $model['model'] ?? 'Unknown',
                $result['table'] ?? 'Unknown',
                $model['columns'] ?? 0,
                ($model['relationships'] ?? 0) + ($model['inverse_relationships'] ?? 0),
                $artifactList,
                $model['existed'] ? 'Overwritten' : 'Created',
            ];
        }

        $this->table(
            ['Model', 'Table', 'Columns', 'Relations', 'Artifacts', 'Status'],
            $tableData
        );
    }

    protected function validateForeignKeys(): void
    {
        $issues = $this->relationshipDetector->validateForeignKeys();

        if (! empty($issues)) {
            $this->warn("\n⚠️  Found ".count($issues).' foreign key issue(s):');

            foreach ($issues as $issue) {
                $this->line("   - {$issue['table']}.{$issue['column']}: {$issue['issue']}");
            }

            $this->newLine();
        } else {
            $this->info('✅ All foreign key references are valid');
        }
    }

    protected function validateConstraintIntegrity(array $tables): void
    {
        $issues = $this->constraintAnalyzer->validateConstraintIntegrity($tables);

        if (! empty($issues)) {
            $this->warn("\n⚠️  Found ".count($issues).' constraint integrity issue(s):');

            foreach ($issues as $issue) {
                $this->line("   - [{$issue['type']}] {$issue['message']}");
            }

            $this->newLine();
        } else {
            $this->info('✅ All constraint references are valid');
        }
    }

    protected function analyzeConstraints(array $tables): void
    {
        $this->info("🔍 Analyzing constraints...\n");

        $summary = $this->constraintAnalyzer->getConstraintSummary($tables);

        $this->info('Constraint Summary:');
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

            if (! empty($analysis['recommendations'])) {
                $hasRecommendations = true;
                $this->line("Table: <comment>{$table}</comment>");

                foreach ($analysis['recommendations'] as $rec) {
                    $icon = match ($rec['type']) {
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

        if (! $hasRecommendations) {
            $this->info('✅ No optimization recommendations - your database structure looks good!');
        }
    }

    protected function displayValidationErrors(ConfigValidator $validator): void
    {
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
        $this->error('Aborting: please fix configuration issues in config/zt-introspection.php');
    }

    protected function displayValidationWarnings(ConfigValidator $validator): void
    {
        $this->warn('⚠️  Configuration warnings detected:');
        foreach ($validator->getFormattedWarnings() as $warning) {
            $this->line("  - {$warning}");
        }
        $this->newLine();
    }
}
