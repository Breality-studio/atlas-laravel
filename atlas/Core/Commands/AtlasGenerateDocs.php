<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;

class AtlasGenerateDocs extends Command
{
    protected $signature = 'atlas:generate-docs';
    protected $description = 'Génère la documentation API automatiquement pour toutes les routes exposées via Scribe';

    public function handle()
    {
        $this->info('Génération de la documentation API via Scribe...');

        // On appelle la commande officielle de Scribe
        $this->call('scribe:generate');

        $this->info('Documentation API générée avec succès dans ' . config('scribe.output_path', 'public/docs'));
    }
}
