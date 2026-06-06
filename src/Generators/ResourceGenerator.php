<?php

namespace Zuqongtech\LaravelDbIntrospection\Generators;

use Zuqongtech\LaravelDbIntrospection\Contracts\Generator;
use Zuqongtech\LaravelDbIntrospection\Support\GenerationOptions;
use Zuqongtech\LaravelDbIntrospection\Support\ModelMetadata;

final class ResourceGenerator implements Generator
{
    public function supports(GenerationOptions $options): bool
    {
        return $options->resources;
    }

    public function getName(): string
    {
        return 'Resource';
    }

    public function generate(ModelMetadata $meta, GenerationOptions $options): array
    {
        $resourceName = $meta->model.'Resource';
        $path = app_path(sprintf('Http/Resources/%s.php', $resourceName));

        if (file_exists($path) && ! $options->force) {
            return [
                'name' => $resourceName,
                'path' => $path,
                'status' => 'skipped',
                'reason' => 'already exists',
            ];
        }

        $content = $this->buildResource($meta);

        if (! $options->dryRun) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($path, $content);
        }

        return [
            'name' => $resourceName,
            'path' => $path,
            'status' => 'success',
            'action' => file_exists($path) ? 'overwritten' : 'created',
        ];
    }

    private function buildResource(ModelMetadata $meta): string
    {
        $resourceName = $meta->model.'Resource';

        $fields = collect($meta->columns)
            ->reject(fn ($col): bool => in_array($col['name'], ['password', 'remember_token', 'api_token']))
            ->map(fn ($col): string => sprintf("            '%s' => \$this->%s,", $col['name'], $col['name']))
            ->implode("\n");

        return <<<PHP
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$resourceName} extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
{$fields}
        ];
    }
}

PHP;
    }
}
