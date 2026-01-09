<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Breality\Atlas\Generators\ContractGenerator;
use Breality\Atlas\Generators\ServiceGenerator;

/**
 * Class MakeService
 *
 * Commande artisan pour générer un service (Feature ou Global) avec tests optionnels
 *
 * @package Breality\AtlasCore\Commands
 */
class MakeService extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:make-service
        {name : Nom du service (ex: User, Auth, Payment)}
        {--g|global : Créer un service global (app/Services)}
        {--c|contract : Générer le contract associé}
        {--t|with-tests : Générer le test unitaire associé}';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Génère un service (Feature ou Global) avec tests optionnels';

    public function handle(): int
    {
        $service = $this->argument('name');
        $isGlobal = (bool) $this->option('global');
        $withContract = (bool) $this->option('contract');
        $withTests = (bool) $this->option('with-tests');

        $this->newLine();
        $this->info('=== Atlas :: Génération Service ===');

        $this->line(sprintf(
            'Service : %s [%s]',
            $service,
            $isGlobal ? 'GLOBAL' : 'FEATURE'
        ));

        $generator = new ServiceGenerator($service, $isGlobal);
        $generator->generate($withTests);

        if ($withContract) {
            $this->newLine();
            $this->info(" Génération du Contract : {$service}Interface");
            $contractGen = new ContractGenerator($service);
            $contractGen->generate();
            $this->info("Contract {$service}Interface généré avec succès !");
        }

        $this->newLine();
        $this->info("Service {$service} généré avec succès !");

        return self::SUCCESS;
    }
}
