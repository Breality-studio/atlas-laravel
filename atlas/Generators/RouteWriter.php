<?php

namespace Breality\Atlas\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class RouteWriter
 *
 * Écrit les routes pour une feature donnée dans les fichiers de routes appropriés
 */
class RouteWriter
{
    protected string $feature;
    protected string $type;

    public function __construct(string $feature, string $type)
    {
        $this->feature = ucfirst($feature);
        $this->type = strtolower($type);

        if (!in_array($this->type, ['api', 'web'])) {
            throw new \InvalidArgumentException("Type de route invalide : {$type}. Utiliser 'api' ou 'web'.");
        }

        // Vérifie si routes activées
        $routeConfig = config("atlas.routes.{$this->type}", []);
        if (!($routeConfig['enabled'] ?? false)) {
            throw new \RuntimeException("Les routes {$this->type} sont désactivées dans la configuration.");
        }
    }

    /**
     * Écrit la route pour la feature
     */
    public function write(): void
    {
        $resource = Str::kebab($this->feature);
        $resourcePlural = $resource . 's';

        $controller = "App\\Features\\{$this->feature}\\{$this->feature}Controller::class";

        $path = base_path("routes/{$this->type}.php");
        if (!File::exists($path)) {
            throw new \RuntimeException("Fichier de routes {$this->type}.php introuvable.");
        }

        // Génération de l'apiResource ou resource
        $snippet = '';
        if ($this->type === 'api') {
            $snippet = "\nRoute::apiResource('{$resourcePlural}', {$controller});";
        } else {
            $snippet = "\nRoute::resource('{$resourcePlural}', {$controller});";
        }

        File::append($path, $snippet);
    }
}
