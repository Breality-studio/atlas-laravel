<?php

namespace Breality\AtlasCore\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class ServiceGenerator
 *
 * Génère un service, soit globalement dans app/Services, soit lié à une feature dans app/Features/{Feature}
 *
 * @package Breality\AtlasCore\Generators
 */
class ServiceGenerator
{
    protected string $service;
    protected bool $global;

    public function __construct(string $service, bool $global = false)
    {
        $this->service = Str::studly($service);
        $this->global = $global;
    }

    public function generate(bool $withTest = false): void
    {
        if ($this->global) {
            $this->generateGlobalService();
        } else {
            $this->generateFeatureService();
        }

        if ($withTest) {
            $this->generateTest();
        }
    }

    /**
     * Service global : app/Services/UserService.php
     */
    protected function generateGlobalService(): void
    {
        $path = app_path('Services');
        $file = "{$path}/{$this->service}Service.php";

        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        if (File::exists($file)) {
            return;
        }

        File::put($file, $this->serviceStub(
            'App\\Services',
            "{$this->service}Service"
        ));
    }

    /**
     * Service lié à une feature : app/Features/User/UserService.php
     */
    protected function generateFeatureService(): void
    {
        $path = app_path("Features/{$this->service}");
        $file = "{$path}/{$this->service}Service.php";

        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        if (File::exists($file)) {
            return;
        }

        File::put($file, $this->serviceStub(
            "App\\Features\\{$this->service}",
            "{$this->service}Service"
        ));
    }

    protected function generateTest(): void
    {
        $testGen = new TestGenerator($this->service);
        $testGen->generate();
    }

    protected function serviceStub(string $namespace, string $class): string
    {
        return <<<PHP
<?php

namespace {$namespace};

class {$class}
{
    /**
     * Logique métier du service
     */
}
PHP;
    }
}
