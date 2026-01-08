<?php

namespace Breality\AtlasCore\Commands;

use Illuminate\Console\Command;

class AtlasNextSteps extends Command
{
    protected $signature = 'atlas:next-steps';

    protected $description = 'Affiche les prochaines étapes après la création du projet Breality Atlas Laravel';

    public function handle(): int
    {
        $projectName = basename(base_path());

        $this->newLine(2);
        $this->components->info('🚀 Projet Breality Atlas Laravel créé avec succès !');
        $this->newLine();

        $this->components->bulletList([
            "Le fichier <fg=yellow>.env</> a été généré à partir de <fg=yellow>.env.example</>",
            "La clé d'application a été générée automatiquement",
        ]);

        $this->newLine();
        $this->components->warn('Prochaines étapes recommandées :');
        $this->newLine();

        $this->line('   1. Entrez dans le répertoire de votre projet :');
        $this->line('      <options=bold>cd ' . $projectName . '</>');
        $this->newLine();

        $this->line('   2. Lancez la configuration interactive complète du projet :');
        $this->line('      <options=bold>php artisan atlas:setup</>');
        $this->line('      → Cette commande vous guidera pas à pas (nom du projet, base de données, stack frontend, etc.)');
        $this->line('      → Elle installera également toutes les dépendances Composer et NPM nécessaires.');
        $this->newLine();

        $this->line('   3. Après le setup, appliquez les migrations :');
        $this->line('      <options=bold>php artisan migrate</>');
        $this->newLine();

        $this->line('   4. Démarrez le serveur de développement :');
        $this->line('      <options=bold>php artisan serve</>');
        $this->newLine();

        $this->line('   5. Si votre stack inclut des assets frontend :');
        $this->line('      <options=bold>npm run dev</>');
        $this->newLine();

        $this->components->info('💡 Astuce : Utilisez <options=bold>php artisan atlas:feature NomDeLaFeature</> pour générer rapidement une fonctionnalité complète (modèles, contrôleurs, routes, tests, documentation...).');

        $this->newLine();
        $this->components->info('🌟 Bon développement avec Breality Atlas !');
        $this->newLine();

        return self::SUCCESS;
    }
}