<?php

namespace Zuqongtech\LaravelDbIntrospection\Contracts;

use Zuqongtech\LaravelDbIntrospection\Support\GenerationOptions;
use Zuqongtech\LaravelDbIntrospection\Support\ModelMetadata;

interface Generator
{
    /**
     * Check if this generator should run based on options
     */
    public function supports(GenerationOptions $options): bool;

    /**
     * Generate the artifact
     */
    public function generate(ModelMetadata $meta, GenerationOptions $options): array;

    /**
     * Get the generator name for display
     */
    public function getName(): string;
}
