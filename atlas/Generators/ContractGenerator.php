<?php

namespace Breality\AtlasCore\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class ContractGenerator
 *
 * Génère un Contract (Interface) pour un service
 *
 * @package Breality\AtlasCore\Generators
 */
class ContractGenerator
{
    protected string $contract;

    public function __construct(string $name)
    {
        $this->contract = Str::studly($name);
    }

    public function generate(): void
    {
        $path = app_path("Contracts");

        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $contractFile = "$path/{$this->contract}Interface.php";

        if (File::exists($contractFile)) {
            throw new \RuntimeException(" {$this->contract}Interface existe déjà !");
        }

        $content = <<<PHP
<?php

namespace App\Contracts;

interface {$this->contract}Interface
{
    /**
     * TODO: Déclarez les méthodes du contract ici
     */
}
PHP;

        File::put($contractFile, $content);
    }
}
