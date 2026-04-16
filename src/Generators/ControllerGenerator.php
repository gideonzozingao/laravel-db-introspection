<?php

namespace Zuqongtech\LaravelDbIntrospection\Generators;

use Illuminate\Support\Str;
use Zuqongtech\LaravelDbIntrospection\Contracts\Generator;
use Zuqongtech\LaravelDbIntrospection\Support\GenerationOptions;
use Zuqongtech\LaravelDbIntrospection\Support\ModelMetadata;

final class ControllerGenerator implements Generator
{
    public function supports(GenerationOptions $options): bool
    {
        return $options->controllers;
    }

    public function getName(): string
    {
        return 'Controller';
    }

    public function generate(ModelMetadata $meta, GenerationOptions $options): array
    {
        $controllerName = $meta->model.'Controller';
        $path = app_path("Http/Controllers/{$controllerName}.php");

        if (file_exists($path) && ! $options->force) {
            return [
                'name' => $controllerName,
                'path' => $path,
                'status' => 'skipped',
                'reason' => 'already exists',
            ];
        }

        $namespace = $options->namespace ?? config('zt-introspection.namespace');
        $content = $this->buildController($meta, $namespace);

        if (! $options->dryRun) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($path, $content);
        }

        return [
            'name' => $controllerName,
            'path' => $path,
            'status' => 'success',
            'action' => file_exists($path) ? 'overwritten' : 'created',
        ];
    }

    protected function buildController(ModelMetadata $meta, string $namespace): string
    {
        $modelClass = $meta->model;
        $modelVariable = Str::camel($modelClass);
        $fullModelClass = trim($namespace, '\\').'\\'.$modelClass;

        return <<<PHP
<?php

namespace App\Http\Controllers;

use {$fullModelClass};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class {$modelClass}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        \${$modelVariable}s = {$modelClass}::all();
        
        return response()->json(\${$modelVariable}s);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request): JsonResponse
    {
        \$validated = \$request->validate([
            // Add validation rules here
        ]);

        \${$modelVariable} = {$modelClass}::create(\$validated);

        return response()->json(\${$modelVariable}, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show({$modelClass} \${$modelVariable}): JsonResponse
    {
        return response()->json(\${$modelVariable});
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, {$modelClass} \${$modelVariable}): JsonResponse
    {
        \$validated = \$request->validate([
            // Add validation rules here
        ]);

        \${$modelVariable}->update(\$validated);

        return response()->json(\${$modelVariable});
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({$modelClass} \${$modelVariable}): JsonResponse
    {
        \${$modelVariable}->delete();

        return response()->json(null, 204);
    }
}

PHP;
    }
}
