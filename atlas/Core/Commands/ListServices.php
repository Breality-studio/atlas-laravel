<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class ListServices
 *
 * Commande artisan pour lister tous les services existants dans app/Services
 *
 * @package Breality\AtlasCore\Commands
 */
class ListServices extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:list-services';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Liste tous les services existants dans app/Services';

    public function handle()
    {
        $servicesPath = app_path('Services');
        if (!File::exists($servicesPath)) {
            $this->warn("Le dossier Services n'existe pas.");
            return Command::SUCCESS;
        }

        $services = File::directories($servicesPath);
        if (empty($services)) {
            $this->info("Aucun service trouvé.");
            return Command::SUCCESS;
        }

        $this->info("=== Liste des services ===");
        foreach ($services as $service) {
            $this->line('- ' . basename($service));
        }

        return Command::SUCCESS;
    }
}
