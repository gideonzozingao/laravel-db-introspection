<?php

namespace Zuqongtech\LaravelDbIntrospection\Support;

class ModelBuilder
{
    protected string $tableName;
    protected string $namespace;
    protected array $columns = [];
    protected array $foreignKeys = [];
    protected ?string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDeletes = false;
    protected bool $withPhpDoc = true;
    protected bool $withInverse = true;
    protected array $inverseRelationships = [];

    public function __construct(string $tableName, string $namespace)
    {
        $this->tableName = $tableName;
        $this->namespace = Helpers::normalizeNamespace($namespace);
    }

    /**
     * Set columns
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set foreign keys
     */
    public function setForeignKeys(array $foreignKeys): self
    {
        $this->foreignKeys = $foreignKeys;
        return $this;
    }

    /**
     * Set primary key
     */
    public function setPrimaryKey(?string $primaryKey): self
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**
     * Set timestamps
     */
    public function setTimestamps(bool $timestamps): self
    {
        $this->timestamps = $timestamps;
        return $this;
    }

    /**
     * Set soft deletes
     */
    public function setSoftDeletes(bool $softDeletes): self
    {
        $this->softDeletes = $softDeletes;
        return $this;
    }

    /**
     * Set with PHP doc
     */
    public function setWithPhpDoc(bool $withPhpDoc): self
    {
        $this->withPhpDoc = $withPhpDoc;
        return $this;
    }

    /**
     * Set with inverse relationships
     */
    public function setWithInverse(bool $withInverse): self
    {
        $this->withInverse = $withInverse;
        return $this;
    }

    /**
     * Add inverse relationship
     */
    public function addInverseRelationship(string $methodName, string $relatedModel, string $foreignKey): self
    {
        $this->inverseRelationships[] = [
            'method' => $methodName,
            'model' => $relatedModel,
            'foreign_key' => $foreignKey,
        ];
        return $this;
    }

    /**
     * Build the model content
     */
    public function build(): string
    {
        $modelName = Helpers::tableToModelName($this->tableName);
        $uses = $this->buildUses();
        $docBlock = $this->withPhpDoc ? $this->buildClassDocBlock() : '';
        $primaryKeyProperty = StubGenerator::primaryKeyStub($this->primaryKey);
        $timestampsProperty = StubGenerator::timestampsStub($this->timestamps);
        $fillable = $this->buildFillable();
        $hidden = $this->buildHidden();
        $casts = $this->buildCasts();
        $dates = $this->buildDates();
        $relationships = $this->buildRelationships();

        $generator = new StubGenerator([
            'namespace' => $this->namespace,
            'uses' => $uses,
            'docblock' => $docBlock,
            'class_name' => $modelName,
            'table' => $this->tableName,
            'primary_key' => $primaryKeyProperty,
            'timestamps' => $timestampsProperty,
            'fillable' => $fillable,
            'hidden' => $hidden,
            'casts' => $casts,
            'dates' => $dates,
            'relationships' => $relationships,
        ]);

        return $generator->generate();
    }

    /**
     * Build uses statements
     */
    protected function buildUses(): string
    {
        $uses = [];
        
        if ($this->softDeletes) {
            $uses[] = 'Illuminate\Database\Eloquent\SoftDeletes';
        }

        return StubGenerator::usesStub($uses);
    }

    /**
     * Build class-level DocBlock
     */
    protected function buildClassDocBlock(): string
    {
        $properties = [];
        $methods = [];

        // Add property documentation
        foreach ($this->columns as $column) {
            $phpType = Helpers::mapDatabaseTypeToPhp($column['type']);
            $phpType = Helpers::isNullableType($phpType, $column['nullable']);
            
            $properties[] = [
                'type' => $phpType,
                'name' => $column['name'],
                'comment' => $column['comment'] ?: null,
            ];
        }

        // Add relationship method documentation
        foreach ($this->foreignKeys as $fk) {
            $methodName = Helpers::foreignKeyToRelationName($fk['column']);
            $relatedModel = Helpers::tableToModelName($fk['referenced_table']);
            
            $methods[] = [
                'return' => "\\{$this->namespace}\\{$relatedModel}",
                'name' => $methodName,
                'comment' => "Get the related {$relatedModel}",
            ];
        }

        // Add inverse relationship documentation
        foreach ($this->inverseRelationships as $inverse) {
            $methods[] = [
                'return' => "\\Illuminate\\Database\\Eloquent\\Collection<int, \\{$this->namespace}\\{$inverse['model']}>",
                'name' => $inverse['method'],
                'comment' => "Get the related {$inverse['model']} records",
            ];
        }

        return StubGenerator::classDocBlock($properties, $methods);
    }

    /**
     * Build fillable property
     */
    protected function buildFillable(): string
    {
        $fillable = [];
        
        foreach ($this->columns as $column) {
            $columnName = $column['name'];
            
            // Skip primary key, timestamps, and auto-increment columns
            if (
                $columnName === $this->primaryKey ||
                Helpers::isTimestampColumn($columnName) ||
                str_contains($column['extra'], 'auto_increment')
            ) {
                continue;
            }
            
            $fillable[] = $columnName;
        }

        return StubGenerator::fillableStub($fillable);
    }

    /**
     * Build hidden property
     */
    protected function buildHidden(): string
    {
        $hidden = [];
        
        foreach ($this->columns as $column) {
            $columnName = $column['name'];
            
            // Hide password and remember_token columns
            if (in_array($columnName, ['password', 'remember_token'])) {
                $hidden[] = $columnName;
            }
        }

        return StubGenerator::hiddenStub($hidden);
    }

    /**
     * Build casts property
     */
    protected function buildCasts(): string
    {
        $casts = [];
        
        foreach ($this->columns as $column) {
            $columnName = $column['name'];
            $castType = Helpers::getCastType($column['type']);
            
            if ($castType && !Helpers::isTimestampColumn($columnName)) {
                $casts[$columnName] = $castType;
            }
        }

        // Add email_verified_at if exists
        $columnNames = array_column($this->columns, 'name');
        if (in_array('email_verified_at', $columnNames)) {
            $casts['email_verified_at'] = 'datetime';
        }

        return StubGenerator::castsStub($casts);
    }

    /**
     * Build dates property (for older Laravel versions)
     */
    protected function buildDates(): string
    {
        // Modern Laravel uses casts for dates, so this is typically empty
        // unless specifically needed for backward compatibility
        return '';
    }

    /**
     * Build relationship methods
     */
    protected function buildRelationships(): string
    {
        $relationships = [];

        // Build belongsTo relationships from foreign keys
        foreach ($this->foreignKeys as $fk) {
            $methodName = Helpers::foreignKeyToRelationName($fk['column']);
            $relatedModel = Helpers::tableToModelName($fk['referenced_table']);
            $fullRelatedModel = $this->namespace . '\\' . $relatedModel;
            
            $relationships[] = StubGenerator::relationshipStub(
                'belongsTo',
                $methodName,
                $fullRelatedModel,
                $fk['column'],
                $fk['referenced_column'],
                $this->withPhpDoc
            );
        }

        // Build hasMany/hasOne inverse relationships
        if ($this->withInverse) {
            foreach ($this->inverseRelationships as $inverse) {
                $fullRelatedModel = $this->namespace . '\\' . $inverse['model'];
                
                $relationships[] = StubGenerator::relationshipStub(
                    'hasMany',
                    $inverse['method'],
                    $fullRelatedModel,
                    $inverse['foreign_key'],
                    null,
                    $this->withPhpDoc
                );
            }
        }

        if (empty($relationships)) {
            return '';
        }

        return "\n" . implode("\n\n", $relationships) . "\n";
    }

    /**
     * Get model name
     */
    public function getModelName(): string
    {
        return Helpers::tableToModelName($this->tableName);
    }

    /**
     * Get full model class
     */
    public function getFullModelClass(): string
    {
        return $this->namespace . '\\' . $this->getModelName();
    }
}