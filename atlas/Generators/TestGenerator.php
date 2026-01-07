<?php

namespace Breality\AtlasCore\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class TestGenerator
 *
 * Génère les tests pour une feature donnée
 *
 * @package Breality\AtlasCore\Generators
 */
class TestGenerator
{
    protected string $feature;

    public function __construct(string $feature)
    {
        $this->feature = ucfirst($feature);
    }

    /**
     * Génère tous les tests
     * @param bool $webRoutes Générer les tests pour les routes web
     */
    public function generate(bool $webRoutes = false): void
    {
        $this->generateFeatureApiTest();
        $this->generateUnitTest();

        if ($webRoutes) {
            $this->generateFeatureWebTest();
        }
    }

    /**
     * Test API CRUD basique
     */
    protected function generateFeatureApiTest(): void
    {
        $path = config('atlas.tests.feature') . "/{$this->feature}";
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $file = "$path/{$this->feature}ApiTest.php";
        $content = "<?php

namespace Tests\Feature\\{$this->feature};

use Tests\TestCase;

class {$this->feature}ApiTest extends TestCase
{
    public function test_index() { \$this->get('/api/" . strtolower($this->feature) . "')->assertStatus(200); }
    public function test_show() { \$this->get('/api/" . strtolower($this->feature) . "/1')->assertStatus(200); }
    public function test_store() { \$this->post('/api/" . strtolower($this->feature) . "', [])->assertStatus(200); }
    public function test_update() { \$this->put('/api/" . strtolower($this->feature) . "/1', [])->assertStatus(200); }
    public function test_destroy() { \$this->delete('/api/" . strtolower($this->feature) . "/1')->assertStatus(200); }
}
";
        File::put($file, $content);
    }

    /**
     * Test Unitaire du Service
     */
    protected function generateUnitTest(): void
    {
        $path = config('atlas.tests.unit') . "/{$this->feature}";
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $file = "$path/{$this->feature}ServiceTest.php";
        $content = "<?php

namespace Tests\Unit\Features\\{$this->feature};

use Tests\TestCase;

class {$this->feature}ServiceTest extends TestCase
{
    public function test_example() { \$this->assertTrue(true); }
}
";
        File::put($file, $content);
    }

    /**
     * Génération optionnelle de tests Web (routes web / Blades)
     */
    protected function generateFeatureWebTest(): void
    {
        $path = base_path("tests/Feature/{$this->feature}");
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $file = "$path/{$this->feature}WebTest.php";
        $content = "<?php

namespace Tests\Feature\\{$this->feature};

use Tests\TestCase;

class {$this->feature}WebTest extends TestCase
{
    public function test_index_page() { \$this->get('/" . Str::kebab($this->feature) . "')->assertStatus(200); }
    public function test_show_page() { \$this->get('/" . Str::kebab($this->feature) . "/1')->assertStatus(200); }
}
";
        File::put($file, $content);
    }
}
