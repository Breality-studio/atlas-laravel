<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;
use Breality\Atlas\Generators\ContractGenerator;

/**
 * Class MakeContract
 *
 * Commande artisan pour générer un Contract (Interface) pour un service
 *
 * @package Breality\AtlasCore\Commands
 */
class MakeContract extends Command
{
    /**
     * Signature et options de la commande
     * @var string
     */
    protected $signature = 'atlas:make-contract {name : Nom de l\'interface à créer}';

    /**
     * Description de la commande
     * @var string
     */
    protected $description = 'Génère un Contract (Interface) pour un service';

    public function handle()
    {
        $contract = $this->argument('name');

        $this->info(" Génération du Contract : {$contract}Interface");

        $generator = new ContractGenerator($contract);
        $generator->generate();

        $this->info("Contract {$contract}Interface généré avec succès !");
    }
}
