<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PDO;

/**
 * Class AtlasSetup
 *
 * Commande artisan pour initialiser un projet avec Breality Atlas
 *
 * @package Breality\AtlasCore\Commands
 */
class AtlasSetup extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:setup';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Initialisation du projet Breality Atlas';

    public function handle()
    {
        $this->info('Bienvenue dans Atlas Setup');

        if (!File::exists(config_path('atlas.php'))) {
            $this->call('vendor:publish', [
                '--tag' => 'atlas-config',
                '--force' => true,
            ]);
            $this->info('Config Atlas publiée.');
        }

        $projectName = $this->ask('Nom du projet');

        $dbType = $this->choice('Type de base de données', ['mysql', 'pgsql', 'sqlite'], 0);

        $dbHost = $dbType !== 'sqlite' ? $this->ask('Host DB', '127.0.0.1') : null;
        $dbUser = $dbType !== 'sqlite' ? $this->ask('User DB', 'root') : null;
        $dbPassword = $dbType !== 'sqlite' ? $this->secret('Password DB') : null;

        $localDbName = $projectName . '_db';
        $testDbName = $projectName . '_test';

        if (!File::exists(base_path('routes/api.php'))) {
            if ($this->confirm('Installer l’API Laravel (api:install) ?', true)) {
                $this->call('api:install');
                $this->info('API installée.');
            }
        }

        $this->info('Configuration des bases de données...');

        try {
            if ($dbType === 'sqlite') {
                $localPath = database_path($localDbName . '.sqlite');
                $testPath = database_path($testDbName . '.sqlite');

                if (!File::exists($localPath)) {
                    File::put($localPath, '');
                    $this->info("DB locale SQLite créée : $localPath");
                } else {
                    $this->info("DB locale SQLite existe déjà : $localPath");
                }

                if (!File::exists($testPath)) {
                    File::put($testPath, '');
                    $this->info("DB test SQLite créée : $testPath");
                } else {
                    $this->info("DB test SQLite existe déjà : $testPath");
                }
            } else {
                $pdoDsn = "$dbType:host=$dbHost";
                $pdo = new PDO($pdoDsn, $dbUser, $dbPassword);

                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$localDbName`");
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$testDbName`");

                $this->info("Bases de données créées : $localDbName et $testDbName");
            }
        } catch (\Exception $e) {
            $this->error("Erreur création DB : " . $e->getMessage());
            return 1;
        }

        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
            $this->info('.env généré à partir de .env.example');
        }

        // Mettre à jour le .env avec les infos saisies
        $envContent = file_get_contents($envPath);

        $replace = [
            'DB_CONNECTION=mysql' => 'DB_CONNECTION=' . $dbType,
            'DB_HOST=127.0.0.1' => 'DB_HOST=' . ($dbHost ?? ''),
            'DB_PORT=3306' => $dbType === 'pgsql' ? 'DB_PORT=5432' : 'DB_PORT=3306',
            'DB_DATABASE=laravel' => 'DB_DATABASE=' . $localDbName,
            'DB_USERNAME=root' => 'DB_USERNAME=' . ($dbUser ?? ''),
            'DB_PASSWORD=' => 'DB_PASSWORD=' . ($dbPassword ?? '')
        ];

        file_put_contents($envPath, str_replace(array_keys($replace), array_values($replace), $envContent));

        $this->info('Fichier .env mis à jour avec succès.');

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

        $this->info('=== Breality Atlas Setup terminé. Vous pouvez maintenant générer vos features et services avec atlas:make ===');
        return 0;
    }
}
