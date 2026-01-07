<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PDO;

class AtlasSetup extends Command
{
    /**
     * Signature et options
     */
    protected $signature = 'atlas:setup 
                            {--name= : Nom du projet} 
                            {--db=mysql : Type de base de données (mysql, pgsql, sqlite)}';

    /**
     * Description de la commande
     */
    protected $description = 'Initialisation du projet Breality Atlas';

    public function handle()
    {
        $this->info('=== Bienvenue dans Atlas Setup ===');

        // 1️⃣ Nom du projet
        $projectName = $this->option('name') ?: $this->ask('Nom du projet');
        $dbType = $this->option('db');

        $dbHost = $dbType !== 'sqlite' ? $this->ask('Host DB', '127.0.0.1') : null;
        $dbUser = $dbType !== 'sqlite' ? $this->ask('User DB', 'root') : null;
        $dbPassword = $dbType !== 'sqlite' ? $this->secret('Password DB') : null;
        $dbPort = $dbType === 'pgsql' ? '5432' : '3306';

        $localDbName = $projectName . '_db';
        $testDbName = $projectName . '_test';

        // 2️⃣ Publier config Atlas si pas existante
        if (!File::exists(config_path('atlas.php'))) {
            $this->call('vendor:publish', [
                '--tag' => 'atlas-config',
                '--force' => true,
            ]);
            $this->info('Config Atlas publiée.');
        }

        // 3️⃣ Création des dossiers Atlas
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

        // 4️⃣ Création des bases de données
        $this->info('Configuration des bases de données...');

        try {
            if ($dbType === 'sqlite') {
                $localPath = database_path($localDbName . '.sqlite');
                $testPath = database_path($testDbName . '.sqlite');

                if (!File::exists($localPath))
                    File::put($localPath, '');
                if (!File::exists($testPath))
                    File::put($testPath, '');

                $this->info("Bases SQLite créées ou déjà existantes : $localPath, $testPath");
            } else {
                $pdoDsn = "$dbType:host=$dbHost;port=$dbPort";
                $pdo = new PDO($pdoDsn, $dbUser, $dbPassword);

                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$localDbName`");
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$testDbName`");

                $this->info("Bases de données créées : $localDbName et $testDbName");
            }
        } catch (\Exception $e) {
            $this->error("Erreur création DB : " . $e->getMessage());
            return 1;
        }

        // 5️⃣ Création / mise à jour du .env
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
            $this->info('.env généré à partir de .env.example');
        }

        $envContent = file_get_contents($envPath);

        $replacements = [
            '/DB_CONNECTION=.*/' => "DB_CONNECTION=$dbType",
            '/DB_HOST=.*/' => "DB_HOST=" . ($dbHost ?? ''),
            '/DB_PORT=.*/' => "DB_PORT=$dbPort",
            '/DB_DATABASE=.*/' => "DB_DATABASE=$localDbName",
            '/DB_USERNAME=.*/' => "DB_USERNAME=" . ($dbUser ?? ''),
            '/DB_PASSWORD=.*/' => "DB_PASSWORD=" . ($dbPassword ?? ''),
        ];

        foreach ($replacements as $pattern => $replacement) {
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n$replacement";
            }
        }

        file_put_contents($envPath, $envContent);
        $this->info('.env mis à jour avec succès.');

        $this->info('=== Breality Atlas Setup terminé. Vous pouvez maintenant générer vos features et services avec atlas:make ===');
        return 0;
    }
}
