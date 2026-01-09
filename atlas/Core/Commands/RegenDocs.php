<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Breality\Atlas\Generators\DocGenerator;
use Illuminate\Support\Facades\File;

/**
 * Class RegenDocs
 *
 * Commande artisan pour régénérer la documentation pour toutes les features Atlas
 *
 * @package Breality\AtlasCore\Commands
 */
class RegenDocs extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:regen-docs';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Régénère la documentation pour toutes les features Atlas';

    public function handle()
    {
        $docsPath = base_path('atlas/Docs');
        $featuresPath = app_path('Features');

        if (!File::exists($featuresPath)) {
            $this->warn("Le dossier Features n'existe pas.");
            return Command::SUCCESS;
        }

        $features = File::directories($featuresPath);
        if (empty($features)) {
            $this->info("Aucune feature à documenter.");
            return Command::SUCCESS;
        }

        foreach ($features as $featureDir) {
            $feature = basename($featureDir);
            $generator = new DocGenerator($feature);
            $generator->generate();
            $this->info("Docs régénérées pour : $feature");
        }

        $this->info("Toutes les docs ont été régénérées");
        return Command::SUCCESS;
    }
}
