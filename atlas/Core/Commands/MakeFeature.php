<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Breality\AtlasCore\Generators\FeatureGenerator;
use Breality\AtlasCore\Generators\RouteWriter;
use Breality\AtlasCore\Generators\TestGenerator;
use Breality\AtlasCore\Generators\DocGenerator;

/**
 * Class MakeFeature
 *
 * Commande artisan pour générer une feature complète :
 * * Controller * Service * Routes * Tests * Documentation
 *
 * @package Breality\AtlasCore\Commands
 */
class MakeFeature extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:make-feature
        {name : Nom de la feature}
        {--t|with-tests : Générer tests unitaires et feature tests}
        {--r|with-routes= : Type de routes à générer (api ou web)}';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Génère une feature complète avec structure, routes, tests et documentation';

    public function handle()
    {
        $feature = ucfirst($this->argument('name'));
        $withTests = $this->option('with-tests');
        $withRoutes = $this->option('with-routes');

        $this->info("=== Génération de la feature : $feature ===");

        // Génération des fichiers principaux
        $generator = new FeatureGenerator($feature);
        $generator->generate();

        if ($withRoutes && in_array($withRoutes, ['api', 'web']) === false) {
            $this->error("L'option --with-routes doit être 'api' ou 'web' si spécifiée.");
            $withRoutes = $this->choice('Selectionner une route valide', ['web', 'api'], 0);
        }

        // Routes
        if ($withRoutes) {
            $routeWriter = new RouteWriter($feature, $withRoutes);
            $routeWriter->write();
            $this->info("Routes {$withRoutes} générées pour la feature {$feature}");
        }

        // Tests
        if ($withTests) {
            $testGenerator = new TestGenerator($feature);
            $testGenerator->generate();
            $this->info("Tests générés pour {$feature}");
        }

        // Documentation
        $docGenerator = new DocGenerator($feature);
        $docGenerator->generate();
        $this->info("Documentation générée pour {$feature}");

        $this->info("=== Feature {$feature} générée avec succès 🎉 ===");

        return Command::SUCCESS;
    }
}
