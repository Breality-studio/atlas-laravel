<?php

namespace Breality\AtlasCore\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class RouteWriter
 *
 * Écrit les routes pour une feature donnée dans les fichiers de routes appropriés
 *
 * @package Breality\AtlasCore\Generators
 */
class RouteWriter
{
    protected string $feature;
    protected string $type;
    protected string $prefix;
    protected array $middleware;

    public function __construct(string $feature, string $type)
    {
        $this->feature = ucfirst($feature);
        $this->type = strtolower($type);

        if (!in_array($this->type, ['api', 'web'])) {
            throw new \InvalidArgumentException("Type de route invalide : {$type}. Utiliser 'api' ou 'web'.");
        }

        // Lecture des configs pour le type de route
        $routeConfig = config("atlas.routes.{$this->type}");
        if (!$routeConfig['enabled'] ?? false) {
            throw new \RuntimeException("Les routes {$this->type} sont désactivées dans la configuration.");
        }

        $this->prefix = $routeConfig['prefix'] ?? '';
        $this->middleware = $routeConfig['middleware'] ?? [];
    }

    public function write(): void
    {
        $resource = Str::kebab($this->feature) . 's';
        $controller = "App\\Features\\{$this->feature}\\{$this->feature}Controller::class";

        $path = base_path("routes/{$this->type}.php");

        if (!File::exists($path)) {
            throw new \RuntimeException("Fichier de routes {$this->type}.php introuvable. Créez le fichier manuellement ou via un setup.");
        }

        // Génération du snippet avec middleware et préfix
        if ($this->type === 'api') {
            $prefix = $this->prefix ? "->prefix('{$this->prefix}')" : '';
            $middleware = $this->middleware ? "->middleware([" . implode(', ', array_map(fn($m) => "'$m'", $this->middleware)) . "])" : '';
            $snippet = "\nRoute::apiResource('$resource', $controller)$prefix$middleware;";
        } else {
            $middleware = $this->middleware ? "->middleware([" . implode(', ', array_map(fn($m) => "'$m'", $this->middleware)) . "])" : '';
            $snippet = "\nRoute::resource('$resource', $controller)$middleware;";
        }

        File::append($path, $snippet);
    }
}
