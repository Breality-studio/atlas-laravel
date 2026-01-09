<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use PDO;
use PDOException;

class AtlasSetup extends Command
{
    protected $signature = 'atlas:setup
                            {--name= : Nom du projet (facultatif, détecté automatiquement si non fourni)}
                            {--db=mysql : Type de base de données (mysql, pgsql, sqlite)}
                            {--host= : Host de la base de données (facultatif)}
                            {--port= : Port de la base de données (facultatif)}
                            {--user= : Utilisateur de la base de données (facultatif)}
                            {--password= : Mot de passe de la base de données (facultatif)}
                            {--frontend= : Stack frontend (blade, api, livewire, inertia-vue, inertia-react, breeze-*, jetstream-*) (facultatif)}
                            {--no-db : Ne pas tenter de créer les bases de données}
                            {--skip-npm : Ne pas exécuter les commandes NPM}';

    protected $description = 'Initialisation complète et interactive du projet Breality Atlas';

    public function handle()
    {
        // Protection contre exécution pendant package:discover
        if (app()->runningInConsole() && $this->getOutput()->isDebug()) {
            $this->warn('Mode package:discover détecté. Exécution d\'AtlasSetup ignorée.');
            return 0;
        }

        $this->info('=== Bienvenue dans Breality Atlas Setup ===');
        $this->newLine();

        try {
            $this->executeSetup();
            $this->displaySuccessSummary();
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur critique : ' . $e->getMessage());
            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    protected function executeSetup(): void
    {
        $isInteractive = $this->isInteractive();

        // 1. Nom du projet
        $projectName = $this->getProjectName($isInteractive);
        $slugProjectName = $this->generateSlug($projectName);
        $localDbName = $slugProjectName . '_db';
        $testDbName = $slugProjectName . '_test';

        $this->info("Projet : {$projectName} ({$slugProjectName})");

        // 2. Configuration base de données
        $dbConfig = $this->getDatabaseConfig($isInteractive, $localDbName, $testDbName);
        $skipDbCreation = (bool) $this->option('no-db');
        $this->createDatabases($dbConfig, $skipDbCreation, $localDbName, $testDbName);

        // 3. Nettoyage composer.json
        $this->updateComposerJson();

        // 4. Publication configuration Atlas
        $this->publishAtlasConfig();

        // 5. Mise à jour .env
        $this->updateEnvFile($dbConfig, $projectName);

        // 6. Stack frontend
        $frontendStack = $this->selectFrontendStack($isInteractive);
        $this->installFrontendStack($frontendStack);

        // 7. Packages supplémentaires
        if ($isInteractive) {
            $this->installAdditionalPackages();
        }

        // 8. Synchronisation Composer
        $this->synchronizeDependencies();

        // 9. NPM
        if (!$this->option('skip-npm')) {
            $this->runNpmInstall($frontendStack);
        }

        // 10. Clé d'application
        $this->generateAppKey();
    }

    private function getProjectName(bool $isInteractive): string
    {
        $default = basename(base_path());
        $name = $this->option('name') ?: ($isInteractive ? $this->ask('Nom du projet', $default) : $default);
        return trim(preg_replace("/['\"]/", '', $name));
    }

    private function generateSlug(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/', '_', $name));
    }

    private function getDatabaseConfig(bool $isInteractive, string $localDbName, string $testDbName): array
    {
        $dbType = $this->option('db') ?: ($isInteractive 
            ? $this->choice('Type de base de données', ['mysql', 'pgsql', 'sqlite'], 'mysql')
            : 'mysql'
        );

        if ($dbType === 'sqlite') {
            return [
                'connection' => 'sqlite',
                'database' => $localDbName,
                'test_database' => $testDbName,
                'host' => null,
                'port' => null,
                'username' => null,
                'password' => null,
            ];
        }

        $host = $this->option('host') ?: ($isInteractive ? $this->ask('Host DB', '127.0.0.1') : '127.0.0.1');
        $port = $this->option('port') ?: ($isInteractive ? $this->ask('Port DB', $dbType === 'pgsql' ? '5432' : '3306') : ($dbType === 'pgsql' ? '5432' : '3306'));
        $user = $this->option('user') ?: ($isInteractive ? $this->ask('Utilisateur DB', 'root') : 'root');
        $pass = $this->option('password') ?: ($isInteractive ? $this->secret('Mot de passe DB') : '');

        return [
            'connection' => $dbType,
            'database' => $localDbName,
            'test_database' => $testDbName,
            'host' => $host,
            'port' => $port,
            'username' => $user,
            'password' => $pass,
        ];
    }

    private function createDatabases(array $config, bool $skip, string $localDbName, string $testDbName): void
    {
        if ($skip) {
            $this->warn('Création des bases de données désactivée (--no-db)');
            return;
        }

        $this->info('Configuration des bases de données...');

        if ($config['connection'] === 'sqlite') {
            File::put(database_path("{$localDbName}.sqlite"), '');
            File::put(database_path("{$testDbName}.sqlite"), '');
            $this->info("Bases SQLite créées : {$localDbName}.sqlite, {$testDbName}.sqlite");
            return;
        }

        try {
            $dsn = "{$config['connection']}:host={$config['host']};port={$config['port']}";
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$localDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("Bases créées : {$localDbName}, {$testDbName}");
        } catch (PDOException $e) {
            $this->warn('Impossible de créer les bases automatiquement : ' . $e->getMessage());
            $this->line("Créez manuellement : {$localDbName} et {$testDbName}");
        }
    }

    private function updateComposerJson(): void
    {
        $path = base_path('composer.json');
        if (File::exists($path)) {
            $composer = json_decode(File::get($path), true);
            if (isset($composer['repositories'])) {
                unset($composer['repositories']);
                File::put($path, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info('composer.json nettoyé (repositories supprimés)');
            }
        }
    }

    private function publishAtlasConfig(): void
    {
        if (!File::exists(config_path('atlas.php'))) {
            try {
                $this->call('vendor:publish', ['--tag' => 'atlas-config', '--force' => true]);
                $this->info('Configuration Atlas publiée');
            } catch (\Exception $e) {
                $this->warn('Publication de la config ignorée : ' . $e->getMessage());
            }
        }
    }

    private function updateEnvFile(array $dbConfig, string $projectName): void
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $replacements = [
            'APP_NAME' => '"' . addslashes($projectName) . '"',
            'DB_CONNECTION' => $dbConfig['connection'],
            'DB_HOST' => $dbConfig['host'] ?? '',
            'DB_PORT' => $dbConfig['port'] ?? '',
            'DB_DATABASE' => $dbConfig['database'],
            'DB_USERNAME' => $dbConfig['username'] ?? '',
            'DB_PASSWORD' => '"' . addslashes($dbConfig['password'] ?? '') . '"',
        ];

        $content = File::get($envPath);
        foreach ($replacements as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $content);
        $this->info('.env configuré');
    }

    private function selectFrontendStack(bool $isInteractive): string
    {
        $stack = $this->option('frontend') ?: 'blade';

        if ($isInteractive) {
            $choice = $this->choice(
                'Stack frontend souhaité',
                [
                    'blade'              => 'Blade (défaut)',
                    'api'                => 'API only (Sanctum)',
                    'livewire'           => 'Livewire',
                    'inertia-vue'        => 'Inertia + Vue 3',
                    'inertia-react'      => 'Inertia + React',
                    'breeze'             => 'Laravel Breeze (préciser variante ensuite)',
                    'jetstream'          => 'Laravel Jetstream (préciser variante ensuite)',
                ],
                'blade'
            );

            $stack = array_search($choice, [
                'blade', 'api', 'livewire', 'inertia-vue', 'inertia-react', 'breeze', 'jetstream'
            ]);

            if (in_array($stack, ['breeze', 'jetstream'])) {
                $variant = $this->ask("Variante {$stack} (ex: blade, api, livewire, inertia-vue, inertia-react)");
                $stack .= '-' . $variant;
            }
        }

        $this->info("Stack sélectionné : {$stack}");
        return $stack;
    }

    protected function installFrontendStack(string $stack): void
    {
        static $executed = [];
        if (isset($executed[$stack])) {
            $this->info("Stack {$stack} déjà installé.");
            return;
        }
        $executed[$stack] = true;

        $this->line("Installation du stack {$stack}...");

        if (str_starts_with($stack, 'breeze-')) {
            $flavor = substr($stack, 7);
            $this->installPackage('laravel/breeze', true);
            $this->call('breeze:install', ['stack' => $flavor]);
            $this->runNpmCommand(['install']);
            $this->runNpmCommand(['run', 'build']);
        } elseif (str_starts_with($stack, 'jetstream-')) {
            $flavor = substr($stack, 10);
            $parts = explode('-', $flavor);
            $base = array_shift($parts);
            $this->installPackage('laravel/jetstream');
            $options = ['stack' => $base];
            if (in_array('api', $parts)) $options['--api'] = true;
            if (in_array('react', $parts)) $options['--react'] = true;
            $this->call('jetstream:install', $options);
            $this->runNpmCommand(['install']);
            $this->runNpmCommand(['run', 'build']);
        } elseif ($stack === 'api') {
            $this->installApiStack();
        } elseif ($stack === 'livewire') {
            $this->installPackage('livewire/livewire');
        } elseif ($stack === 'inertia-vue') {
            $this->installPackage('inertiajs/inertia-laravel');
            $this->runNpmCommand(['install', '@inertiajs/vue3']);
        } elseif ($stack === 'inertia-react') {
            $this->installPackage('inertiajs/inertia-laravel');
            $this->runNpmCommand(['install', '@inertiajs/react']);
        } else {
            $this->installBladeStack();
        }
    }
    private function installApiStack(): void
    {
        $this->call('vendor:publish', ['--provider' => 'Laravel\Sanctum\SanctumServiceProvider']);
        $this->createApiExample();
        $this->cleanupWebViews();
        $this->info('Stack API installé');
    }

    private function installBladeStack(): void
    {
        $this->createWelcomeController();
        $this->updateWebRoutes();
        $this->info('Stack Blade installé');
    }

    private function createWelcomeController(): void
    {
        $path = app_path('Http/Controllers/WelcomeController.php');
        if (!File::exists($path)) {
            File::put($path, $this->getWelcomeControllerStub());
            $this->info('WelcomeController créé');
        }
    }

    private function updateWebRoutes(): void
    {
        $path = base_path('routes/web.php');
        $content = File::get($path);
        if (!str_contains($content, 'WelcomeController')) {
            $content = str_replace("view('welcome')", "[\\App\\Http\\Controllers\\WelcomeController::class, 'index']", $content);
            File::put($path, $content);
            $this->info('Route web mise à jour');
        }
    }

    private function createApiExample(): void
    {
        $dir = app_path('Http/Controllers/Api');
        File::makeDirectory($dir, 0755, true);
        $controllerPath = $dir . '/ExampleController.php';
        if (!File::exists($controllerPath)) {
            File::put($controllerPath, $this->getApiControllerStub());
        }

        $routesPath = base_path('routes/api.php');
        $routesContent = File::get($routesPath);
        if (!str_contains($routesContent, 'ExampleController')) {
            File::append($routesPath, "\nRoute::get('/example', [\\App\\Http\\Controllers\\Api\\ExampleController::class, 'index']);");
        }
        $this->info('Exemple API créé');
    }

    private function cleanupWebViews(): void
    {
        $viewPath = resource_path('views/welcome.blade.php');
        if (File::exists($viewPath)) {
            File::delete($viewPath);
            $this->info('Vue welcome supprimée (mode API)');
        }
    }

    private function installAdditionalPackages(): void
    {
        $input = $this->ask('Packages supplémentaires (séparés par virgule, :dev pour dev)');
        if (trim($input) === '') {
            return;
        }

        foreach (explode(',', $input) as $pkg) {
            $pkg = trim($pkg);
            if ($pkg === '') continue;
            $dev = str_ends_with($pkg, ':dev');
            $packageName = $dev ? substr($pkg, 0, -4) : $pkg;
            $this->installPackage($packageName, $dev);
        }
    }

    private function synchronizeDependencies(): void
    {
        $this->info('Synchronisation des dépendances Composer...');
        $process = new Process([
            'composer', 'update', '--optimize-autoloader', '--no-interaction', '--no-scripts'
        ], base_path());
        $process->setTimeout(900);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $this->info($process->isSuccessful() ? 'Dépendances synchronisées' : 'Échec Composer');
    }

    private function runNpmInstall(string $frontendStack): void
    {
        $needsNpm = str_contains($frontendStack, 'inertia') ||
                    str_starts_with($frontendStack, 'breeze-') ||
                    str_starts_with($frontendStack, 'jetstream-');

        if ($needsNpm && File::exists(base_path('package.json'))) {
            $this->info('Installation des dépendances NPM...');
            $this->runNpmCommand(['install']);
            $this->runNpmCommand(['run', 'build']);
        }
    }

    private function runNpmCommand(array $command): void
    {
        $process = new Process(array_merge(['npm'], $command), base_path());
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $this->line($process->isSuccessful() ? 'NPM réussi' : 'Échec NPM');
    }

    private function generateAppKey(): void
    {
        $this->info('Génération de la clé d\'application...');
        $this->call('key:generate', ['--force' => true]);
        $this->info('Clé générée');
    }

    private function installPackage(string $package, bool $dev = false): void
    {
        $this->info("Installation de {$package}" . ($dev ? ' (dev)' : ''));

        $command = ['composer', 'require', $package];
        if ($dev) {
            $command[] = '--dev';
        }

        $process = new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']);
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $this->info($process->isSuccessful() ? "{$package} installé" : "Échec {$package}");
    }

    private function displaySuccessSummary(): void
    {
        $this->newLine(2);
        $this->info('=== Breality Atlas Setup terminé avec succès ===');
        $this->newLine();
        $this->line('Prochaines étapes :');
        $this->line('   php artisan migrate');
        $this->line('   php artisan serve');
        $this->line('   npm run dev');
        $this->newLine();
        $this->line('Commandes Atlas :');
        $this->line('   php artisan atlas:make-feature NomFeature');
        $this->line('   php artisan atlas:make-service NomService');
        $this->newLine();
    }

    protected function isInteractive(): bool
    {
        return $this->input->isInteractive() && (function_exists('stream_isatty') ? stream_isatty(STDIN) : true);
    }

    private function getWelcomeControllerStub(): string
    {
        return "<?php\n\nnamespace App\\Http\\Controllers;\n\nuse Illuminate\\View\\View;\n\nclass WelcomeController extends Controller\n{\n    public function index(): View\n    {\n        return view('welcome');\n    }\n}\n";
    }

    private function getApiControllerStub(): string
    {
        return "<?php\n\nnamespace App\\Http\\Controllers\\Api;\n\nuse App\\Http\\Controllers\\Controller;\n\nclass ExampleController extends Controller\n{\n    public function index()\n    {\n        return response()->json([\n            'message' => 'Breality Atlas API prête !',\n            'status' => 'active'\n        ]);\n    }\n}\n";
    }
}