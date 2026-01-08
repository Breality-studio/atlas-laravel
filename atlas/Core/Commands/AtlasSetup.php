<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PDO;
use PDOException;

class AtlasSetup extends Command
{
    protected $signature = 'atlas:setup
                            {--name= : Nom du projet (facultatif, détecté automatiquement si non fourni)}
                            {--db=mysql : Type de base de données (mysql, pgsql, sqlite)}';

    protected $description = 'Initialisation du projet Breality Atlas';

    public function handle()
    {
        $this->info('=== Bienvenue dans Atlas Setup ===');

        // 1. Nom du projet – détection automatique si non fourni
        $projectName = $this->option('name');
        if (!$projectName) {
            $projectName = basename(base_path());
            $this->info("Nom du projet détecté automatiquement : {$projectName}");
        }

        $dbType = $this->option('db') ?: 'mysql';

        // Valeurs par défaut sécurisées (modifiables manuellement dans .env après installation)
        $dbHost = $dbType !== 'sqlite' ? '127.0.0.1' : null;
        $dbPort = $dbType === 'pgsql' ? '5432' : '3306';
        $dbUser = $dbType !== 'sqlite' ? 'root' : null;
        $dbPassword = $dbType !== 'sqlite' ? '' : null;

        $localDbName = strtolower(preg_replace('/[^a-z0-9]/', '_', $projectName)) . '_db';
        $testDbName = strtolower(preg_replace('/[^a-z0-9]/', '_', $projectName)) . '_test';

        // 2. Publication de la configuration Atlas
        if (!File::exists(config_path('atlas.php'))) {
            $this->call('vendor:publish', [
                '--tag' => 'atlas-config',
                '--force' => true,
            ]);
            $this->info('Config Atlas publiée.');
        }

        // 3. Création des dossiers Atlas
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

        // 4. Création des bases de données (avec gestion d'erreurs améliorée)
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

        // 5. Mise à jour du fichier .env
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
            $this->info('.env généré à partir de .env.example');
        }

        $envContent = file_get_contents($envPath);

        $replacements = [
            'DB_CONNECTION' => $dbType,
            'DB_HOST'       => $dbHost ?? '',
            'DB_PORT'       => $dbPort,
            'DB_DATABASE'   => $localDbName,
            'DB_USERNAME'   => $dbUser ?? '',
            'DB_PASSWORD'   => $dbPassword ?? '',
        ];

        foreach ($replacements as $key => $value) {
            $pattern = "/^{$key}=(.*)$/m";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
        $this->info('.env mis à jour avec les paramètres par défaut.');

        $this->info('=== Breality Atlas Setup terminé avec succès ===');
        $this->comment('Vous pouvez maintenant modifier les paramètres de base de données dans .env si nécessaire, puis exécuter :');
        $this->line('php artisan migrate');
        $this->line('php artisan atlas:make ...');

        return 0;
    }
}