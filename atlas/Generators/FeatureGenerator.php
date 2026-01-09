<?php

namespace Breality\Atlas\Generators;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
/**
 * Class FeatureGenerator
 *
 * Génère la structure de base pour une feature Atlas
 *
 * @package Breality\AtlasCore\Generators
 */
class FeatureGenerator
{
    protected string $feature;

    public function __construct(string $feature)
    {
        $this->feature = $feature;
    }

    public function generate(): void
    {
        $featurePath = config('atlas.feature_base_path') . "/{$this->feature}";
        if (!File::exists($featurePath)) {
            File::makeDirectory($featurePath, 0755, true);
        }

        $this->generateController($featurePath);
        $this->generateService($featurePath);
        $this->generateRequests($featurePath);
        $this->generateResources($featurePath);
    }

    protected function generateController(string $path): void
    {
        $controllerPath = "$path/{$this->feature}Controller.php";

        if (!File::exists($controllerPath)) {
            $content = "<?php

namespace App\Features\\{$this->feature};

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class {$this->feature}Controller extends Controller
{
    public function index() {}
    public function show(\$id) {}
    public function store(Request \$request) {}
    public function update(Request \$request, \$id) {}
    public function destroy(\$id) {}
}
";
            File::put($controllerPath, $content);
        }

        $this->addPhpDoc($controllerPath);
    }

    protected function generateService(string $path): void
    {
        $servicePath = "$path/{$this->feature}Service.php";
        if (!File::exists($servicePath)) {
            $content = "<?php

namespace App\Features\\{$this->feature};

class {$this->feature}Service
{
    // TODO: logiques métiers
}
";
            File::put($servicePath, $content);
        }
    }

    protected function generateRequests(string $path): void
    {
        $requestsPath = "$path/Requests";
        if (!File::exists($requestsPath)) {
            File::makeDirectory($requestsPath, 0755, true);
        }

        foreach (['Store', 'Update'] as $r) {
            $className = $r . $this->feature . 'Request';
            $filePath = "$requestsPath/$className.php";
            if (!File::exists($filePath)) {
                $content = "<?php

namespace App\Features\\{$this->feature}\Requests;

use Illuminate\Foundation\Http\FormRequest;

class $className extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return []; }
}
";
                File::put($filePath, $content);
            }
        }
    }

    protected function generateResources(string $path): void
    {
        $resourcesPath = "$path/Resources";
        if (!File::exists($resourcesPath)) {
            File::makeDirectory($resourcesPath, 0755, true);
        }

        $className = $this->feature . 'Resource';
        $filePath = "$resourcesPath/$className.php";
        if (!File::exists($filePath)) {
            $content = "<?php

namespace App\Features\\{$this->feature}\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class $className extends JsonResource
{
    public function toArray(\$request)
    {
        return parent::toArray(\$request);
    }
}
";
            File::put($filePath, $content);
        }
    }

    public function addPhpDoc(string $controllerPath): void
    {
        if (!class_exists($controllerClass = "App\\Features\\{$this->feature}\\{$this->feature}Controller")) {
            return;
        }

        $ref = new ReflectionClass($controllerClass);
        $methods = array_filter(
            $ref->getMethods(ReflectionMethod::IS_PUBLIC),
            fn($m) => $m->class === $controllerClass
        );

        $content = File::get($controllerPath);

        // Supprime les anciens PHPDoc pour les méthodes supprimées
        $content = preg_replace('/\s*\/\*\*[\s\S]*?\*\/\s*public function (\w+)\(/', 'public function $1(', $content);

        foreach ($methods as $method) {
            $name = $method->getName();

            // Ignore si PHPDoc déjà présent
            if (preg_match("/\/\*\*[\s\S]*\*\/\s*public function $name\(/", $content))
                continue;

            $http = match ($name) {
                'index' => 'GET',
                'show' => 'GET',
                'store' => 'POST',
                'update' => 'PUT',
                'destroy' => 'DELETE',
                default => 'GET'
            };

            $route = match ($name) {
                'index', 'store' => "/api/" . strtolower($this->feature),
                'show', 'update', 'destroy' => "/api/" . strtolower($this->feature) . "/{id}",
                default => "/api/" . strtolower($this->feature) . "/$name"
            };

            $phpDoc = <<<DOC
    /**
     * [$http] $route
     * Description: $name method
     * @group {$this->feature}
     * @authenticated false
     */
DOC;

            $content = preg_replace(
                "/(public function $name\(.*\)\s*{)/",
                $phpDoc . "\n    $1",
                $content
            );
        }

        File::put($controllerPath, $content);
    }
}
