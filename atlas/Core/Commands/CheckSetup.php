<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

/**
 * Class CheckSetup
 *
 * Commande artisan pour vérifier que Atlas est correctement configuré (DB, routes, Docs)
 *
 * @package Breality\AtlasCore\Commands
 */
class CheckSetup extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:check-setup';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Vérifie que Atlas est correctement configuré (DB, routes, Docs)';

    public function handle()
    {
        $this->info("=== Vérification Atlas ===");

        // DB
        try {
            DB::connection()->getPdo();
            $this->info("Base de données accessible : " . config('database.default'));
        } catch (\Exception $e) {
            $this->error("Base de données inaccessible : " . $e->getMessage());
        }

        // Routes
        $routesPath = [base_path('routes/web.php'), base_path('routes/api.php')];
        foreach ($routesPath as $path) {
            if (File::exists($path)) {
                $this->info("Route file OK : $path");
            } else {
                $this->warn("⚠ Route file manquant : $path");
            }
        }

        // Docs
        $docsPath = base_path('atlas/Docs');
        if (File::exists($docsPath)) {
            $this->info("Docs folder OK : $docsPath");
        } else {
            $this->warn("⚠ Docs folder manquant : $docsPath");
        }

        $this->info("=== Vérification terminée ===");
        return Command::SUCCESS;
    }
}
