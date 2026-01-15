<?php

namespace Zuqongtech\LaravelDbIntrospection\Generators;

use Zuqongtech\LaravelDbIntrospection\Contracts\Generator;
use Zuqongtech\LaravelDbIntrospection\Support\GenerationOptions;
use Zuqongtech\LaravelDbIntrospection\Support\ModelMetadata;

final class PolicyGenerator implements Generator
{
    public function supports(GenerationOptions $options): bool
    {
        return $options->policies;
    }

    public function getName(): string
    {
        return 'Policy';
    }

    public function generate(ModelMetadata $meta, GenerationOptions $options): array
    {
        $policyName = $meta->model.'Policy';
        $path = app_path("Policies/{$policyName}.php");

        if (file_exists($path) && ! $options->force) {
            return [
                'name' => $policyName,
                'path' => $path,
                'status' => 'skipped',
                'reason' => 'already exists',
            ];
        }

        $namespace = $options->namespace ?? config('zt-introspection.namespace');
        $content = $this->buildPolicy($meta, $namespace);

        if (! $options->dryRun) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($path, $content);
        }

        return [
            'name' => $policyName,
            'path' => $path,
            'status' => 'success',
            'action' => file_exists($path) ? 'overwritten' : 'created',
        ];
    }

    protected function buildPolicy(ModelMetadata $meta, string $namespace): string
    {
        $policyName = $meta->model.'Policy';
        $modelClass = $meta->model;
        $fullModelClass = trim($namespace, '\\').'\\'.$modelClass;
        $modelVariable = lcfirst($modelClass);

        // Check if user_id column exists for ownership logic
        $hasUserOwnership = collect($meta->columns)->contains('name', 'user_id');
        $ownershipCheck = $hasUserOwnership
            ? "return \$user->id === \${$modelVariable}->user_id;"
            : 'return true;';

        return <<<PHP
<?php

namespace App\Policies;

use {$fullModelClass};
use App\Models\User;

class {$policyName}
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User \$user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User \$user, {$modelClass} \${$modelVariable}): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User \$user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User \$user, {$modelClass} \${$modelVariable}): bool
    {
        {$ownershipCheck}
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User \$user, {$modelClass} \${$modelVariable}): bool
    {
        {$ownershipCheck}
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User \$user, {$modelClass} \${$modelVariable}): bool
    {
        {$ownershipCheck}
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User \$user, {$modelClass} \${$modelVariable}): bool
    {
        {$ownershipCheck}
    }
}

PHP;
    }
}
