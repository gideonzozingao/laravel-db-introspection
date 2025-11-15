<?php

namespace Zuqongtech\LaravelDbIntrospection\Support;

class StubGenerator
{
    protected array $replacements = [];
    
    public function __construct(array $replacements = [])
    {
        $this->replacements = $replacements;
    }

    /**
     * Add a replacement
     */
    public function addReplacement(string $key, string $value): self
    {
        $this->replacements[$key] = $value;
        return $this;
    }

    /**
     * Add multiple replacements
     */
    public function addReplacements(array $replacements): self
    {
        $this->replacements = array_merge($this->replacements, $replacements);
        return $this;
    }

    /**
     * Generate model stub
     */
    public function generate(): string
    {
        $stub = $this->getModelStub();
        
        foreach ($this->replacements as $key => $value) {
            $stub = str_replace("{{$key}}", $value, $stub);
        }
        
        return $stub;
    }

    /**
     * Get base model stub template
     */
    protected function getModelStub(): string
    {
        return <<<'STUB'
<?php

namespace {namespace};

use Illuminate\Database\Eloquent\Model;
{uses}

{docblock}
class {class_name} extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '{table}';
{primary_key}
{timestamps}
{constraint_comments}
{fillable}
{hidden}
{casts}
{dates}
{relationships}
}

STUB;
    }

    /**
     * Generate relationship method stub
     */
    public static function relationshipStub(
        string $type,
        string $methodName,
        string $relatedModel,
        ?string $foreignKey = null,
        ?string $localKey = null,
        bool $withDocBlock = true
    ): string {
        $docBlock = '';
        if ($withDocBlock) {
            $relationType = ucfirst($type);
            $docBlock = "    /**\n";
            $docBlock .= "     * Get the {$methodName} relationship.\n";
            $docBlock .= "     *\n";
            $docBlock .= "     * @return \\Illuminate\\Database\\Eloquent\\Relations\\{$relationType}\n";
            $docBlock .= "     */\n";
        }

        $params = "'{$relatedModel}'";
        
        if ($foreignKey) {
            $params .= ", '{$foreignKey}'";
        }
        
        if ($localKey) {
            $params .= ", '{$localKey}'";
        }

        return $docBlock . "    public function {$methodName}()\n" .
               "    {\n" .
               "        return \$this->{$type}({$params});\n" .
               "    }";
    }

    /**
     * Generate fillable array stub
     */
    public static function fillableStub(array $columns, int $indent = 1): string
    {
        if (empty($columns)) {
            return '';
        }

        $indentation = str_repeat('    ', $indent);
        $innerIndent = str_repeat('    ', $indent + 1);
        
        $stub = "\n{$indentation}/**\n";
        $stub .= "{$indentation} * The attributes that are mass assignable.\n";
        $stub .= "{$indentation} *\n";
        $stub .= "{$indentation} * @var array<int, string>\n";
        $stub .= "{$indentation} */\n";
        $stub .= "{$indentation}protected \$fillable = [\n";
        
        foreach ($columns as $column) {
            $stub .= "{$innerIndent}'{$column}',\n";
        }
        
        $stub .= "{$indentation}];";
        
        return $stub;
    }

    /**
     * Generate hidden array stub
     */
    public static function hiddenStub(array $columns, int $indent = 1): string
    {
        if (empty($columns)) {
            return '';
        }

        $indentation = str_repeat('    ', $indent);
        $innerIndent = str_repeat('    ', $indent + 1);
        
        $stub = "\n{$indentation}/**\n";
        $stub .= "{$indentation} * The attributes that should be hidden for serialization.\n";
        $stub .= "{$indentation} *\n";
        $stub .= "{$indentation} * @var array<int, string>\n";
        $stub .= "{$indentation} */\n";
        $stub .= "{$indentation}protected \$hidden = [\n";
        
        foreach ($columns as $column) {
            $stub .= "{$innerIndent}'{$column}',\n";
        }
        
        $stub .= "{$indentation}];";
        
        return $stub;
    }

    /**
     * Generate casts array stub
     */
    public static function castsStub(array $casts, int $indent = 1): string
    {
        if (empty($casts)) {
            return '';
        }

        $indentation = str_repeat('    ', $indent);
        $innerIndent = str_repeat('    ', $indent + 1);
        
        $stub = "\n{$indentation}/**\n";
        $stub .= "{$indentation} * The attributes that should be cast.\n";
        $stub .= "{$indentation} *\n";
        $stub .= "{$indentation} * @var array<string, string>\n";
        $stub .= "{$indentation} */\n";
        $stub .= "{$indentation}protected \$casts = [\n";
        
        foreach ($casts as $column => $cast) {
            $stub .= "{$innerIndent}'{$column}' => '{$cast}',\n";
        }
        
        $stub .= "{$indentation}];";
        
        return $stub;
    }

    /**
     * Generate dates array stub
     */
    public static function datesStub(array $dates, int $indent = 1): string
    {
        if (empty($dates)) {
            return '';
        }

        $indentation = str_repeat('    ', $indent);
        $innerIndent = str_repeat('    ', $indent + 1);
        
        $stub = "\n{$indentation}/**\n";
        $stub .= "{$indentation} * The attributes that should be mutated to dates.\n";
        $stub .= "{$indentation} *\n";
        $stub .= "{$indentation} * @var array<int, string>\n";
        $stub .= "{$indentation} */\n";
        $stub .= "{$indentation}protected \$dates = [\n";
        
        foreach ($dates as $date) {
            $stub .= "{$innerIndent}'{$date}',\n";
        }
        
        $stub .= "{$indentation}];";
        
        return $stub;
    }

    /**
     * Generate class-level PHPDoc
     */
    public static function classDocBlock(array $properties, array $methods): string
    {
        $lines = [];
        
        if (!empty($properties)) {
            foreach ($properties as $property) {
                $type = $property['type'] ?? 'mixed';
                $name = $property['name'];
                $comment = $property['comment'] ?? null;
                
                // Skip empty property definitions (used for table comments)
                if (empty($type) && empty($name)) {
                    if ($comment) {
                        $lines[] = $comment;
                    }
                    continue;
                }
                
                if ($comment) {
                    $lines[] = "@property {$type} \${$name} {$comment}";
                } else {
                    $lines[] = "@property {$type} \${$name}";
                }
            }
        }
        
        if (!empty($properties) && !empty($methods)) {
            $lines[] = '';
        }
        
        if (!empty($methods)) {
            foreach ($methods as $method) {
                $return = $method['return'] ?? 'mixed';
                $name = $method['name'];
                $comment = $method['comment'] ?? null;
                
                if ($comment) {
                    $lines[] = "@method {$return} {$name}() {$comment}";
                } else {
                    $lines[] = "@method {$return} {$name}()";
                }
            }
        }
        
        if (empty($lines)) {
            return '';
        }
        
        return Helpers::formatDocBlock($lines, 0);
    }

    /**
     * Generate primary key property stub
     */
    public static function primaryKeyStub(?string $primaryKey, int $indent = 1): string
    {
        // If null or 'id', don't add the property (use Laravel's default)
        if ($primaryKey === null || $primaryKey === 'id') {
            return '';
        }

        $indentation = str_repeat('    ', $indent);
        
        return "\n{$indentation}/**\n" .
               "{$indentation} * The primary key for the model.\n" .
               "{$indentation} *\n" .
               "{$indentation} * @var string\n" .
               "{$indentation} */\n" .
               "{$indentation}protected \$primaryKey = '{$primaryKey}';";
    }

    /**
     * Generate timestamps property stub
     */
    public static function timestampsStub(bool $timestamps, int $indent = 1): string
    {
        if ($timestamps) {
            return '';
        }

        $indentation = str_repeat('    ', $indent);
        
        return "\n{$indentation}/**\n" .
               "{$indentation} * Indicates if the model should be timestamped.\n" .
               "{$indentation} *\n" .
               "{$indentation} * @var bool\n" .
               "{$indentation} */\n" .
               "{$indentation}public \$timestamps = false;";
    }

    /**
     * Generate uses statements
     */
    public static function usesStub(array $uses): string
    {
        if (empty($uses)) {
            return '';
        }

        $statements = [];
        foreach ($uses as $use) {
            $statements[] = "use {$use};";
        }

        return implode("\n", $statements);
    }
}