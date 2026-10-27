# ERP AI – Laminas

> Application ERP développée avec le framework **Laminas MVC**, intégrant des fonctionnalités d'**intelligence artificielle**.

<!-- Badges (à adapter) -->
![PHP](https://img.shields.io/badge/PHP-8.1%20|%208.2%20|%208.3-777BB4?logo=php&logoColor=white)
![Laminas](https://img.shields.io/badge/Laminas-MVC-2B6CB0)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)
![License](https://img.shields.io/badge/License-BSD--3--Clause-green)

---

## Table des matières

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Architecture du projet](#architecture-du-projet)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Lancement](#lancement)
- [Création des utilisateurs](#création-des-utilisateurs)
- [Tests et qualité du code](#tests-et-qualité-du-code)
- [Déploiement](#déploiement)
- [Contribuer](#contribuer)
- [Licence](#licence)
- [Auteur](#auteur)

---

## Présentation

**ERP AI Laminas** est une application web de gestion d'entreprise (ERP) construite sur le framework Laminas MVC.
Elle centralise la gestion des processus métier (facturation, utilisateurs, etc.) et s'appuie sur l'IA pour automatiser et assister certaines tâches.

> ✏️ **À compléter :** décrivez en 2-3 phrases le problème que le projet résout et son public cible.

## Fonctionnalités

- 🔐 **Authentification** sécurisée par jeton JWT et gestion de sessions
- 👤 **Gestion des utilisateurs** et des administrateurs
- 🧾 **Module Facturation** (`Invoice`)
- 🤖 **Intégration IA** via appels à des API externes (client HTTP Guzzle)

> ✏️ **À compléter :** listez les fonctionnalités réelles (ex. : extraction de données de factures par IA, assistant de saisie, prédictions, etc.) ainsi que les autres modules ERP prévus (clients, stock, comptabilité…).

## Stack technique

| Couche | Technologie |
|---|---|
| Langage | PHP 8.1 / 8.2 / 8.3 |
| Framework | Laminas MVC |
| Authentification | `firebase/php-jwt`, `laminas/laminas-session` |
| Client HTTP (API IA) | `guzzlehttp/guzzle` |
| Conteneurisation | Docker, Docker Compose |
| Qualité de code | PHPUnit, Psalm, PHP_CodeSniffer |

## Architecture du projet

```
erp-ai-laminas/
├── bin/                  # Scripts utilitaires (cache, etc.)
├── config/               # Configuration de l'application
├── data/cache/           # Cache applicatif
├── module/
│   ├── Application/      # Module principal
│   └── Invoice/          # Module de facturation
├── public/               # Point d'entrée web (index.php)
├── create_admin.php      # Script de création d'un administrateur
├── create_user.php       # Script de création d'un utilisateur
├── Dockerfile
├── docker-compose.yml
├── composer.json
├── phpcs.xml             # Règles de style de code
├── phpunit.xml.dist      # Configuration des tests
└── psalm.xml             # Analyse statique
```

## Prérequis

- **PHP** 8.1, 8.2 ou 8.3
- **[Composer](https://getcomposer.org/)**
- **Docker** et **Docker Compose** (optionnel, recommandé)
- Une base de données (> ✏️ à préciser : MySQL, PostgreSQL…)
- Une clé d'API pour le service d'IA utilisé (> ✏️ à préciser)

## Installation

### Option 1 : installation locale

```bash
# 1. Cloner le dépôt
git clone https://github.com/kammounimen/erp-ai-laminas.git
cd erp-ai-laminas

# 2. Installer les dépendances
composer install

# 3. Activer le mode développement
composer development-enable
```

### Option 2 : avec Docker

```bash
git clone https://github.com/kammounimen/erp-ai-laminas.git
cd erp-ai-laminas

docker-compose up -d --build
```

L'application est alors disponible sur **http://localhost:8080**.

Pour exécuter des commandes dans le conteneur :

```bash
docker-compose run laminas composer install
```

## Configuration

Les fichiers de configuration se trouvent dans `config/`. Pour une configuration locale, créez vos fichiers dans `config/autoload/` (ex. : `local.php`), qui ne doivent **pas** être versionnés.

> ✏️ **À compléter :** indiquez les paramètres à renseigner, par exemple :

```php
<?php
// config/autoload/local.php
return [
    'db' => [
        // paramètres de connexion à la base de données
    ],
    'jwt' => [
        'secret' => 'votre-clé-secrète',
    ],
    'ai' => [
        'api_key' => 'votre-clé-api',
        'endpoint' => 'https://...',
    ],
];
```

⚠️ Ne publiez jamais de clés secrètes ou d'identifiants dans le dépôt Git.

## Lancement

```bash
# Serveur PHP intégré (développement uniquement)
composer serve
# ou
php -S 0.0.0.0:8080 -t public
```

Puis ouvrez **http://localhost:8080**.

### Commandes Composer utiles

| Commande | Description |
|---|---|
| `composer serve` | Lance le serveur de développement |
| `composer development-enable` | Active le mode développement |
| `composer development-disable` | Désactive le mode développement |
| `composer development-status` | Affiche l'état du mode développement |
| `composer clear-config-cache` | Vide le cache de configuration |

## Création des utilisateurs

Deux scripts permettent de créer des comptes en ligne de commande :

```bash
# Créer un administrateur
php create_admin.php

# Créer un utilisateur standard
php create_user.php
```

> ✏️ **À compléter :** précisez les arguments attendus ou les identifiants par défaut (à changer impérativement en production).

## Tests et qualité du code

```bash
# Tests unitaires
composer test

# Analyse statique (Psalm)
composer static-analysis

# Vérification du style de code
composer cs-check

# Correction automatique du style
composer cs-fix
```

## Déploiement

### Apache

```apache
<VirtualHost *:80>
    ServerName erp.example.com
    DocumentRoot /chemin/vers/erp-ai-laminas/public
    <Directory /chemin/vers/erp-ai-laminas/public>
        DirectoryIndex index.php
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen       80;
    server_name  erp.example.com;
    root         /chemin/vers/erp-ai-laminas/public;

    location / {
        index index.php;
        try_files $uri $uri/ @php;
    }

    location @php {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_param  SCRIPT_FILENAME /chemin/vers/erp-ai-laminas/public/index.php;
        include fastcgi_params;
    }
}
```

> Le serveur intégré de PHP est réservé au développement. Utilisez Apache ou Nginx avec PHP-FPM en production.

## Contribuer

Les contributions sont les bienvenues :

1. Forkez le projet
2. Créez une branche : `git checkout -b feature/ma-fonctionnalite`
3. Committez vos changements : `git commit -m "Ajout de ma fonctionnalité"`
4. Poussez la branche : `git push origin feature/ma-fonctionnalite`
5. Ouvrez une Pull Request

Avant toute PR, lancez `composer cs-check` et `composer test`.

## Licence

Ce projet est distribué sous licence **BSD-3-Clause**. Voir les fichiers [LICENSE.md](LICENSE.md) et [COPYRIGHT.md](COPYRIGHT.md).

## Auteur

**kammounimen** – [github.com/kammounimen](https://github.com/kammounimen)
