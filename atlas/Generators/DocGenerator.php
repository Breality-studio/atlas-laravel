<?php

namespace Breality\AtlasCore\Generators;

use Illuminate\Support\Facades\File;

/**
 * Class DocGenerator
 *
 * Génère la documentation pour une feature donnée
 *
 * @package Breality\AtlasCore\Generators
 */
class DocGenerator
{
    protected string $feature;

    public function __construct(string $feature)
    {
        $this->feature = ucfirst($feature);
    }

    public function generate(): void
    {
        $controllerClass = "App\\Features\\{$this->feature}\\{$this->feature}Controller";

        if (!class_exists($controllerClass)) return;

        $ref = new \ReflectionClass($controllerClass);
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        $docsPath = base_path("atlas/Docs/{$this->feature}");
        if (!File::exists($docsPath)) File::makeDirectory($docsPath, 0755, true);

        $content = "# Documentation de {$this->feature}\n\n";
        $content .= "| Méthode HTTP | Endpoint | Description |\n|-------------|---------|------------|\n";

        foreach ($methods as $method) {
            if ($method->class !== $controllerClass) continue; // ignore inherited methods

            $name = $method->getName();
            $http = match ($name) {
                'index' => 'GET',
                'show' => 'GET',
                'store' => 'POST',
                'update' => 'PUT',
                'destroy' => 'DELETE',
                default => 'GET'
            };

            $route = match ($name) {
                'index', 'store' => "/api/".strtolower($this->feature),
                'show', 'update', 'destroy' => "/api/".strtolower($this->feature)."/{id}",
                default => "/api/".strtolower($this->feature)."/$name"
            };

            $content .= "| $http | $route | {$name} method |\n";
        }

        File::put("$docsPath/endpoints.md", $content);

        // Génération README
        $readme = "# {$this->feature} Feature\n";
        $readme .= "Endpoints et documentation générée automatiquement.\n";
        File::put("$docsPath/README.md", $readme);
    }
}
