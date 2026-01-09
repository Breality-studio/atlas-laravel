<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Breality\Atlas\Generators\DocGenerator;
use Breality\Atlas\Generators\FeatureGenerator;

/**
 * Class WatchDocs
 *
 * Commande artisan pour surveiller les controllers et régénérer automatiquement la documentation des features modifiées
 *
 * @package Breality\AtlasCore\Commands
 */
class WatchDocs extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:watch-docs
        {--interval=2 : Intervalle en secondes pour vérifier les modifications}';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Surveille les controllers et régénère automatiquement la documentation des features modifiées';

    protected array $lastModified = [];

    public function handle(): int
    {
        $interval = config('atlas.watcher.interval', 2);
        $this->info("Atlas :: Watcher Documentation (interval: {$interval}s)");

        $featuresPath = app_path('Features');

        if (!File::exists($featuresPath)) {
            $this->warn("Le dossier Features n'existe pas.");
            return self::SUCCESS;
        }

        $this->scanFiles($featuresPath);

        while (true) {
            sleep($interval);
            $this->checkChanges($featuresPath);
        }

        return self::SUCCESS;
    }

    protected function scanFiles(string $path): void
    {
        foreach (File::directories($path) as $featureDir) {
            $feature = basename($featureDir);
            $controllerPath = "$featureDir/{$feature}Controller.php";
            if (File::exists($controllerPath)) {
                $this->lastModified[$feature] = File::lastModified($controllerPath);
            }
        }
    }

    protected function checkChanges(string $path): void
    {
        foreach (File::directories($path) as $featureDir) {
            $feature = basename($featureDir);
            $controllerPath = "$featureDir/{$feature}Controller.php";
            if (!File::exists($controllerPath))
                continue;

            $lastMod = File::lastModified($controllerPath);

            if (!isset($this->lastModified[$feature]) || $lastMod > $this->lastModified[$feature]) {
                $this->lastModified[$feature] = $lastMod;

                $this->info("🔄 Changement détecté dans {$feature}Controller");

                try {
                    (new FeatureGenerator($feature))->addPhpDoc($controllerPath);
                    (new DocGenerator($feature))->generate();

                    $this->call('scribe:generate');

                    $this->info("Documentation mise à jour pour {$feature}");
                } catch (\Throwable $e) {
                    $this->error("Erreur lors de la génération docs pour {$feature}: {$e->getMessage()}");
                }
            }
        }
    }
}
