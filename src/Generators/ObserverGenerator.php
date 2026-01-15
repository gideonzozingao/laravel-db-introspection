<?php

namespace Zuqongtech\LaravelDbIntrospection\Generators;

use Zuqongtech\LaravelDbIntrospection\Contracts\Generator;
use Zuqongtech\LaravelDbIntrospection\Support\GenerationOptions;
use Zuqongtech\LaravelDbIntrospection\Support\ModelMetadata;

class ObserverGenerator implements Generator
{
    // Observer generation logic will go here

    public function supports(GenerationOptions $options): bool
    {
        return $options->observers;
    }

    public function getName(): string
    {
        return 'Observer';
    }

    public function generate(ModelMetadata $meta, GenerationOptions $options): array
    {
        $observerName = $meta->model.'Observer';
        $path = app_path("Observers/{$observerName}.php");

        if (file_exists($path) && ! $options->force) {
            return [
                'name' => $observerName,
                'path' => $path,
                'status' => 'skipped',
                'reason' => 'already exists',
            ];
        }

        $namespace = $options->namespace ?? config('zt-introspection.namespace');
        $content = $this->buildObserver($meta, $namespace);

        if (! $options->dryRun) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($path, $content);
        }

        return [
            'name' => $observerName,
            'path' => $path,
            'status' => 'success',
            'action' => file_exists($path) ? 'overwritten' : 'created',
        ];
    }

    protected function buildObserver(ModelMetadata $meta, string $namespace): string
    {
        $observerName = $meta->model.'Observer';
        $modelClass = $meta->model;
        $fullModelClass = trim($namespace, '\\').'\\'.$modelClass;
        $modelVariable = lcfirst($modelClass);

        $methods = '';

        if ($meta->timestamps) {
            $methods .= <<<PHP

    /**
     * Handle the {$modelClass} "created" event.
     */
    public function created({$modelClass} \${$modelVariable}): void
    {
        // Log creation, send notifications, etc.
    }

    /**
     * Handle the {$modelClass} "updated" event.
     */
    public function updated({$modelClass} \${$modelVariable}): void
    {
        // Log updates, invalidate cache, etc.
    }
PHP;
        }

        if ($meta->softDeletes) {
            $methods .= <<<PHP


    /**
     * Handle the {$modelClass} "deleted" event.
     */
    public function deleted({$modelClass} \${$modelVariable}): void
    {
        // Handle soft delete
    }

    /**
     * Handle the {$modelClass} "restored" event.
     */
    public function restored({$modelClass} \${$modelVariable}): void
    {
        // Handle restoration
    }
PHP;
        } else {
            $methods .= <<<PHP


    /**
     * Handle the {$modelClass} "deleted" event.
     */
    public function deleted({$modelClass} \${$modelVariable}): void
    {
        // Handle permanent deletion
    }
PHP;
        }

        $methods .= <<<PHP


    /**
     * Handle the {$modelClass} "force deleted" event.
     */
    public function forceDeleted({$modelClass} \${$modelVariable}): void
    {
        // Handle force deletion
    }
PHP;

        return <<<PHP
<?php

namespace App\Observers;

use {$fullModelClass};

class {$observerName}
{
{$methods}
}

PHP;
    }
}
