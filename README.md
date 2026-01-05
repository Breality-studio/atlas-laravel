# Atlas-Laravel

Atlas-Laravel est le **starter officiel Breality** pour Laravel.  
Il sert à standardiser le développement de projets internes et futurs, en fournissant :

- Un **starter Laravel** toujours à jour (dernière version stable)
- Des **générateurs de Features et Services**
- Une **documentation automatique**
- Des **tests unitaires et fonctionnels automatisés**
- Un **workflow standardisé** respectant PSR-12

## Objectif

Créer un projet Laravel prêt à l'emploi pour tous les projets Breality, en garantissant :

- Cohérence dans le code et la structure
- Installation rapide et automatisée

## Structure du projet

```
atlas-laravel/
│
├── README.md
├── composer.json
├── .env.example
├── .gitignore
├── atlas/
│   ├── Core/        # Logique centrale, helpers, configuration
│   ├── Generators/  # Génération de Features et Services
│   ├── Docs/        # Documentation automatique
│   └── Tests/       # Tests automatiques
├── app/             # Laravel standard
├── routes/
├── database/
├── resources/
├── tests/
└── artisan

```

## Prochaines étapes

- Créer la commande `atlas:setup` pour configurer le projet
- Vérification et création de la base de données (locale + test)
- Génération automatique de Features et Services
- Mise en place de la documentation et des tests automatiques
```

---

## **4️⃣ `.gitignore` initial**

```
/vendor
/node_modules
/.env
/.idea
/.vscode
/public/storage
/storage/*.key
```

---

## **5️⃣ Composer.json minimal pour Phase 0**

```json
{
    "name": "breality/atlas-laravel",
    "type": "project",
    "require": {
        "php": "^8.1",
        "laravel/framework": "*"
    },
    "autoload": {
        "psr-4": {
            "Breality\\AtlasCore\\": "atlas/Core/"
        }
    },
    "scripts": {
        "post-create-project-cmd": [
            "@php artisan atlas:setup"
        ]
    }
}
```

* Laravel prendra **toujours la dernière version stable** grâce à `"laravel/framework": "*"`
* `post-create-project-cmd` prépare la future commande interactive (`atlas:setup`)

---


