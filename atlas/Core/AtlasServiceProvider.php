<?php

namespace Breality\AtlasCore;

use Illuminate\Support\ServiceProvider;

/**
 * Class AtlasServiceProvider
 *
 * Fournit les commandes artisan et l'auto-binding des Contracts aux Services
 *
 * @package Breality\AtlasCore
 */
class AtlasServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            \Breality\AtlasCore\Commands\AtlasSetup::class,
            \Breality\AtlasCore\Commands\CheckSetup::class,
            \Breality\AtlasCore\Commands\MakeContract::class,
            \Breality\AtlasCore\Commands\MakeFeature::class,
            \Breality\AtlasCore\Commands\ListFeatures::class,
            \Breality\AtlasCore\Commands\CleanFeature::class,
            \Breality\AtlasCore\Commands\MakeService::class,
            \Breality\AtlasCore\Commands\ListServices::class,
            \Breality\AtlasCore\Commands\WatchDocs::class,
            \Breality\AtlasCore\Commands\RegenDocs::class,

            // \Breality\AtlasCore\Commands\AtlasGenerateDocs::class
        ]);

        $this->autoBindContracts();
    }

    protected function autoBindContracts()
    {
        $contractsPath = app_path('Contracts');
        if (!is_dir($contractsPath)) {
            return;
        }

        foreach (glob($contractsPath . '/*Interface.php') as $file) {
            $contract = 'App\\Contracts\\' . basename($file, '.php');
            $service = 'App\\Services\\' . str_replace('Interface', '', basename($file, '.php'));

            if (class_exists($service) && interface_exists($contract)) {
                $this->app->bind($contract, $service);
            }
        }
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/atlas.php' => config_path('atlas.php'),
        ], 'config');
    }
}
