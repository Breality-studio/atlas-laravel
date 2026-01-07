<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class CleanFeature
 *
 * Commande artisan pour supprimer une feature et tous ses fichiers générés (tests, docs, controller, service)
 *
 * @package Breality\AtlasCore\Commands
 */
class CleanFeature extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:clean {feature : Nom de la feature à supprimer}';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Supprime une feature et tous ses fichiers générés (tests, docs, controller, service)';

    public function handle()
    {
        $feature = $this->argument('feature');
        $paths = [
            app_path("Features/$feature"),
            base_path("tests/Feature/$feature"),
            base_path("tests/Unit/Features/$feature"),
            base_path("atlas/Docs/$feature"),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
                $this->info("Supprimé : $path");
            }
        }

        $this->info("=== Feature $feature supprimée avec succès ===");
        return Command::SUCCESS;
    }
}
