<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PDO;
use PDOException;
use Symfony\Component\Process\Process;

class AtlasSetup extends Command
{
    protected $signature = 'atlas:setup
                            {--name= : Nom du projet (facultatif, détecté automatiquement si non fourni)}
                            {--vendor= : Nom du vendor pour composer.json (facultatif)}
                            {--db=mysql : Type de base de données (mysql, pgsql, sqlite)}
                            {--host= : Host de la base de données (facultatif)}
                            {--port= : Port de la base de données (facultatif)}
                            {--user= : Utilisateur de la base de données (facultatif)}
                            {--password= : Mot de passe de la base de données (facultatif)}
                            {--frontend= : Stack frontend (blade, api, livewire, inertia-vue, inertia-react, breeze-blade, breeze-api, breeze-livewire, breeze-inertia-vue, breeze-inertia-react, jetstream-livewire, jetstream-api, jetstream-inertia-vue, jetstream-inertia-react) (facultatif)}';

    protected $description = 'Initialisation du projet Breality Atlas';

    public function handle()
    {
        $this->info('=== Bienvenue dans Breality Atlas Setup ===');

        // Détection si l'environnement est interactif
        $isInteractive = $this->isInteractive();

        // 1. Nom du projet – détection automatique si non fourni
        $defaultProjectName = basename(base_path());
        $projectName = $this->option('name') ?: ($isInteractive ? $this->ask('Nom du projet', $defaultProjectName) : $defaultProjectName);

        // Nom du vendor pour composer.json
        $defaultVendor = $projectName ?? 'breality';
        $vendor = $this->option('vendor') ?: ($isInteractive ? $this->ask('Nom du vendor (pour composer.json)', $defaultVendor) : $defaultVendor);

        $dbType = $this->option('db') ?: ($isInteractive ? $this->choice('Type de base de données', ['mysql', 'pgsql', 'sqlite'], 'mysql') : 'mysql');

        // Valeurs par défaut pour la DB, avec prompts si interactif
        $defaultHost = $dbType !== 'sqlite' ? '127.0.0.1' : null;
        $dbHost = $this->option('host') ?: ($dbType !== 'sqlite' && $isInteractive ? $this->ask('Host DB', $defaultHost) : $defaultHost);

        $defaultPort = $dbType === 'pgsql' ? '5432' : ($dbType === 'mysql' ? '3306' : null);
        $dbPort = $this->option('port') ?: ($dbType !== 'sqlite' && $isInteractive ? $this->ask('Port DB', $defaultPort) : $defaultPort);

        $defaultUser = $dbType !== 'sqlite' ? 'root' : null;
        $dbUser = $this->option('user') ?: ($dbType !== 'sqlite' && $isInteractive ? $this->ask('User DB', $defaultUser) : $defaultUser);

        $dbPassword = $this->option('password');
        if ($dbPassword === null && $dbType !== 'sqlite') {
            $dbPassword = $isInteractive ? ($this->secret('Password DB') ?? '') : '';
        }

        $slugProjectName = strtolower(preg_replace('/[^a-z0-9]/', '_', $projectName));
        $localDbName = $slugProjectName . '_db';
        $testDbName = $slugProjectName . '_test';

        // 2. Mise à jour de composer.json
        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);
            $newPackageName = strtolower($vendor) . '/' . strtolower(preg_replace('/[^a-z0-9]/', '-', $projectName));
            $composer['name'] = $newPackageName;

            // Optionnel : mise à jour de la description
            $defaultDescription = $composer['description'] ?? 'Mon projet Atlas Laravel';
            $description = $isInteractive ? $this->ask('Description du projet', $defaultDescription) : $defaultDescription;
            $composer['description'] = $description;

            // Suppression du repository VCS si présent
            unset($composer['repositories']);

            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('composer.json mis à jour avec le nouveau nom de package : ' . $newPackageName);
        } else {
            $this->warn('Fichier composer.json non trouvé. Mise à jour ignorée.');
        }

        // 3. Publication de la configuration Atlas
        if (!File::exists(config_path('atlas.php'))) {
            $this->call('vendor:publish', [
                '--tag' => 'atlas-config',
                '--force' => true,
            ]);
            $this->info('Config Atlas publiée.');
        }

        // 4. Création des dossiers Atlas
        $atlasDirs = [
            base_path('atlas/Core'),
            base_path('atlas/Generators'),
            base_path('atlas/Docs'),
            base_path('atlas/Tests'),
        ];

        foreach ($atlasDirs as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->info("Dossier créé : $dir");
            }
        }

        // 5. Création des bases de données (avec gestion d'erreurs améliorée)
        $this->info('Configuration des bases de données...');

        try {
            if ($dbType === 'sqlite') {
                $localPath = database_path("{$localDbName}.sqlite");
                $testPath = database_path("{$testDbName}.sqlite");

                if (!File::exists($localPath)) {
                    File::put($localPath, '');
                }
                if (!File::exists($testPath)) {
                    File::put($testPath, '');
                }

                $this->info("Bases SQLite créées : {$localPath}, {$testPath}");
            } else {
                $pdoDsn = "{$dbType}:host={$dbHost};port={$dbPort}";
                $pdo = new PDO($pdoDsn, $dbUser, $dbPassword);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$localDbName}`");
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDbName}`");

                $this->info("Bases de données créées : {$localDbName} et {$testDbName}");
            }
        } catch (PDOException $e) {
            $this->warn("Impossible de créer les bases de données automatiquement : " . $e->getMessage());
            $this->comment("Vous devrez créer manuellement les bases {$localDbName} et {$testDbName} ou ajuster les paramètres dans .env.");
        }

        // 6. Mise à jour du fichier .env
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
            $this->info('.env généré à partir de .env.example');
        }

        $envContent = file_get_contents($envPath);

        $replacements = [
            'APP_NAME' => preg_replace("/['\"]/", '', $projectName),
            'DB_CONNECTION' => $dbType,
            'DB_HOST' => $dbHost ?? '',
            'DB_PORT' => $dbPort ?? '',
            'DB_DATABASE' => $localDbName,
            'DB_USERNAME' => $dbUser ?? '',
            'DB_PASSWORD' => $dbPassword ?? '',
        ];

        foreach ($replacements as $key => $value) {
            $pattern = "/^{$key}=(.*)$/m";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}=" . addslashes($value), $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
        $this->info('.env mis à jour avec les paramètres par défaut.');

        // 7. Configuration du moteur de template / stack frontend
        $defaultFrontend = 'blade';
        $frontendOptions = [
            'blade' => 'Blade (défaut, avec vues de base)',
            'api' => 'API only (avec Sanctum, sans frontend)',
            'livewire' => 'Livewire',
            'inertia-vue' => 'Inertia avec Vue',
            'inertia-react' => 'Inertia avec React',
            'breeze-blade' => 'Breeze avec Blade',
            'breeze-api' => 'Breeze avec API (Sanctum)',
            'breeze-livewire' => 'Breeze avec Livewire',
            'breeze-inertia-vue' => 'Breeze avec Inertia + Vue',
            'breeze-inertia-react' => 'Breeze avec Inertia + React',
            'jetstream-livewire' => 'Jetstream avec Livewire',
            'jetstream-api' => 'Jetstream avec API (Sanctum)',
            'jetstream-inertia-vue' => 'Jetstream avec Inertia + Vue',
            'jetstream-inertia-react' => 'Jetstream avec Inertia + React',
        ];
        $frontendLabel = $isInteractive ? $this->choice('Choisissez le stack frontend / moteur de template', array_values($frontendOptions), $frontendOptions[$defaultFrontend]) : $frontendOptions[$defaultFrontend];
        $frontendKey = $this->option('frontend') ?: array_search($frontendLabel, $frontendOptions) ?: $defaultFrontend;

        $this->info("Configuration du stack frontend : {$frontendOptions[$frontendKey]}");

        // Installation basée sur le choix
        $this->installFrontendStack($frontendKey);

        // 8. Installations supplémentaires
        if ($isInteractive) {
            $additionalPackages = $this->ask('Voulez-vous installer des packages supplémentaires ? (séparés par virgule, ex: package1,package2:dev)', '');
            if (!empty($additionalPackages)) {
                $packages = array_map('trim', explode(',', $additionalPackages));
                foreach ($packages as $pkg) {
                    $isDev = str_ends_with($pkg, ':dev');
                    $pkgName = $isDev ? str_replace(':dev', '', $pkg) : $pkg;
                    $this->installPackage($pkgName, $isDev);
                }
            }
        }

        // 9. Installation finale des dépendances Composer
        $this->newLine();
        $this->info('Installation des dépendances Composer (composer install)...');
        $process = new Process(['composer', 'install', '--optimize-autoloader', '--no-interaction']);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('Échec de l\'installation des dépendances Composer.');
            return 1;
        }
        $this->info('Dépendances Composer installées avec succès.');

        // 10. Installation des dépendances Node.js si nécessaire
        $frontendNeedsNpm = in_array($frontendKey, [
            'inertia-vue',
            'inertia-react',
            'breeze-blade',
            'breeze-livewire',
            'breeze-inertia-vue',
            'breeze-inertia-react',
            'jetstream-livewire',
            'jetstream-inertia-vue',
            'jetstream-inertia-react'
        ]);

        if ($frontendNeedsNpm && File::exists(base_path('package.json'))) {
            $this->newLine();
            $this->info('Installation des dépendances NPM (npm install)...');
            $this->runNpmCommand(['install']);
            $this->info('Dépendances NPM installées. Vous pouvez lancer le frontend avec : npm run dev');
        }

        // 11. Génération de la clé d'application
        $this->newLine();
        $this->info('Génération de la clé d\'application...');
        $this->call('key:generate', ['--ansi' => true, '--force' => true]);

        $this->newLine();
        $this->info('=== Breality Atlas Setup terminé avec succès ===');
        $this->comment('Vous pouvez maintenant :');
        $this->line('• Exécuter les migrations : php artisan migrate');
        $this->line('• Lancer le serveur : php artisan serve');
        $this->line('• Compiler les assets (si frontend) : npm run dev');
        $this->line('• Utiliser les commandes Atlas : php artisan atlas:make ...');

        return 0;
    }

    /**
     * Vérifie si l'environnement est interactif (TTY disponible).
     */
    protected function isInteractive(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDIN);
    }

    /**
     * Installe un package via Composer.
     */
    protected function installPackage(string $package, bool $dev = false): void
    {
        $this->info("Installation de {$package}" . ($dev ? ' (--dev)' : '') . '...');
        $command = ['composer', 'require', $package];
        if ($dev) {
            $command[] = '--dev';
        }
        $process = new Process($command);
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        if (!$process->isSuccessful()) {
            $this->error("Échec de l'installation de {$package}.");
        } else {
            $this->info("{$package} installé avec succès.");
        }
    }

    /**
     * Exécute une commande Artisan.
     */
    protected function runArtisanCommand(array $command): void
    {
        $process = new Process(array_merge(['php', 'artisan'], $command));
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        if (!$process->isSuccessful()) {
            $this->error('Échec de la commande Artisan : ' . implode(' ', $command));
        }
    }

    /**
     * Exécute une commande NPM.
     */
    protected function runNpmCommand(array $command): void
    {
        $process = new Process(array_merge(['npm'], $command));
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        if (!$process->isSuccessful()) {
            $this->error('Échec de la commande NPM : ' . implode(' ', $command));
        } else {
            $this->info('Commande NPM exécutée avec succès.');
        }
    }

    /**
     * Installe le stack frontend choisi.
     */
    protected function installFrontendStack(string $stack): void
    {
        switch ($stack) {
            case 'api':
                $this->runArtisanCommand(['vendor:publish', '--provider=Laravel\Sanctum\SanctumServiceProvider']);
                if (!File::exists(app_path('Http/Controllers/Api/ExampleController.php'))) {
                    File::makeDirectory(app_path('Http/Controllers/Api'), 0755, true);
                    File::put(app_path('Http/Controllers/Api/ExampleController.php'), "<?php\n\nnamespace App\\Http\\Controllers\\Api;\n\nuse App\\Http\\Controllers\\Controller;\n\nclass ExampleController extends Controller\n{\n    public function index()\n    {\n        return response()->json(['message' => 'API ready']);\n    }\n}");
                    $this->info('Controller API de base créé.');
                }
                $apiRoutesPath = base_path('routes/api.php');
                $apiRoutesContent = File::get($apiRoutesPath);
                if (strpos($apiRoutesContent, 'ExampleController') === false) {
                    File::append($apiRoutesPath, "\nRoute::get('/example', [ExampleController::class, 'index']);");
                    $this->info('Route API de base ajoutée.');
                }
                File::delete(resource_path('views/welcome.blade.php'));
                $this->info('Vues web supprimées pour mode API only.');
                break;

            case 'livewire':
                $this->installPackage('livewire/livewire');
                break;

            case 'inertia-vue':
                $this->installPackage('inertiajs/inertia-laravel');
                $this->runNpmCommand(['install', '@inertiajs/vue3']);
                break;

            case 'inertia-react':
                $this->installPackage('inertiajs/inertia-laravel');
                $this->runNpmCommand(['install', '@inertiajs/react']);
                break;

            case 'breeze-blade':
                $this->installPackage('laravel/breeze', true);
                $this->runArtisanCommand(['breeze:install', 'blade']);
                $this->runNpmCommand(['install']);
                $this->runNpmCommand(['run', 'build']);
                break;

            case 'breeze-api':
                $this->installPackage('laravel/breeze', true);
                $this->runArtisanCommand(['breeze:install', 'api']);
                break;

            case 'breeze-livewire':
                $this->installPackage('laravel/breeze', true);
                $this->runArtisanCommand(['breeze:install', 'livewire']);
                $this->runNpmCommand(['install']);
                $this->runNpmCommand(['run', 'build']);
                break;

            case 'breeze-inertia-vue':
                $this->installPackage('laravel/breeze', true);
                $this->runArtisanCommand(['breeze:install', 'vue']);
                $this->runNpmCommand(['install']);
                $this->runNpmCommand(['run', 'build']);
                break;

            case 'breeze-inertia-react':
                $this->installPackage('laravel/breeze', true);
                $this->runArtisanCommand(['breeze:install', 'react']);
                $this->runNpmCommand(['install']);
                $this->runNpmCommand(['run', 'build']);
                break;

            case 'jetstream-livewire':
                $this->installPackage('laravel/jetstream');
                $this->runArtisanCommand(['jetstream:install', 'livewire']);
                $this->runNpmCommand(['install']);
                $this->runNpmCommand(['run', 'build']);
                break;

            case 'jetstream-api':
                $this->installPackage('laravel/jetstream');
                $this->runArtisanCommand(['jetstream:install', 'livewire', '--api']);
                $this->runNpmCommand(['install']);
                $this->runNpmCommand(['run', 'build']);
                break;

            case 'jetstream-inertia-vue':
                $this->installPackage('laravel/jetstream');
                $this->runArtisanCommand(['jetstream:install', 'inertia']);
                $this->runNpmCommand(['install']);
                $this->runNpmCommand(['run', 'build']);
                break;

            case 'jetstream-inertia-react':
                $this->installPackage('laravel/jetstream');
                $this->runArtisanCommand(['jetstream:install', 'inertia', '--react']);
                $this->runNpmCommand(['install']);
                $this->runNpmCommand(['run', 'build']);
                break;

            case 'blade':
            default:
                $this->info('Stack Blade par défaut sélectionné. Configuration des éléments de base...');
                if (!File::exists(app_path('Http/Controllers/WelcomeController.php'))) {
                    File::put(app_path('Http/Controllers/WelcomeController.php'), "<?php\n\nnamespace App\\Http\\Controllers;\n\nuse Illuminate\\View\\View;\n\nclass WelcomeController extends Controller\n{\n    public function index(): View\n    {\n        return view('welcome');\n    }\n}");
                    $this->info('Controller de base créé.');
                }
                $webRoutesPath = base_path('routes/web.php');
                $webRoutesContent = File::get($webRoutesPath);
                if (strpos($webRoutesContent, 'WelcomeController') === false) {
                    $webRoutesContent = str_replace("view('welcome')", "WelcomeController::class . '@index'", $webRoutesContent);
                    File::put($webRoutesPath, $webRoutesContent);
                    $this->info('Route web de base mise à jour.');
                }
                break;
        }
    }
}