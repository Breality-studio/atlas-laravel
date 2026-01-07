<?php
/**
 * Configuration pour le package Atlas
 *
 * Définit les chemins de base pour les features et services,
 * les paramètres des routes, la surveillance des modifications,
 * la génération de documentation et les conventions de test.
 *
 * @return array
 */
return [
    /*|--------------------------------------------------------------------------
    | Chemins de base
    |-------------------------------------------------------------------------- */
    'feature_base_path' => app_path('Features'),
    'service_base_path' => app_path('Services'),

    /*|--------------------------------------------------------------------------
    | Routes
    |-------------------------------------------------------------------------- */
    'routes' => [
        'api' => [
            'enabled' => true,
            'prefix' => 'api',
            'middleware' => ['api'],
        ],
        'web' => [
            'enabled' => true,
            'middleware' => ['web'],
        ],
    ],

    /*|--------------------------------------------------------------------------
    | Watcher
    |-------------------------------------------------------------------------- */
    'watcher' => [
        'enabled' => true,
        'interval' => 2,
        'sync_docs' => true,
    ],

    /*|--------------------------------------------------------------------------
    | Documentation
    |-------------------------------------------------------------------------- */
    'docs' => [
        'tool' => 'scribe',
        'path' => base_path('atlas/Docs'),
        'auto_regen' => true,
    ],

    /*|--------------------------------------------------------------------------
    | Tests
    |-------------------------------------------------------------------------- */
    'tests' => [
        'unit' => base_path('tests/Unit/Features'),
        'feature' => base_path('tests/Feature'),
        'naming_convention' => '{Feature}{Type}Test.php',
    ],

    /*|--------------------------------------------------------------------------
    | Contracts
    |-------------------------------------------------------------------------- */
    'contracts' => [
        'auto_bind' => true,
    ],
];
