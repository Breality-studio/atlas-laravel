<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class ListFeatures
 *
 * Commande artisan pour lister toutes les features existantes dans app/Features
 *
 * @package Breality\AtlasCore\Commands
 */
class ListFeatures extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:list-features';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Liste toutes les features existantes dans app/Features';

    public function handle()
    {
        $featuresPath = app_path('Features');
        if (!File::exists($featuresPath)) {
            $this->warn("Le dossier Features n'existe pas.");
            return Command::SUCCESS;
        }

        $features = File::directories($featuresPath);
        if (empty($features)) {
            $this->info("Aucune feature trouvée.");
            return Command::SUCCESS;
        }

        $this->info("=== Liste des features ===");
        foreach ($features as $feature) {
            $this->line('- ' . basename($feature));
        }

        return Command::SUCCESS;
    }
}
