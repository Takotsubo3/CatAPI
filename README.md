# CatAPI

API REST dédiée à la gestion de données de chats, construite avec Symfony et
API Platform.

> **État du projet :** la structure technique est en place, mais aucune
> ressource métier ni migration n'est encore définie.

## Stack technique

- PHP `>= 8.2`
- Symfony `7.4`
- API Platform `5`
- Doctrine ORM `3`
- SQLite par défaut en développement
- Nelmio CORS

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- L'extension PHP `ctype`
- L'extension PHP `iconv`

Le serveur Symfony CLI est recommandé pour le développement, mais le serveur
PHP intégré peut également être utilisé.

## Installation

```bash
git clone https://github.com/Takotsubo3/CatAPI.git
cd CatAPI
composer install
```

Les variables d'environnement de développement sont déjà configurées dans
`.env`. La base de données utilisée par défaut est un fichier SQLite situé
dans `var/data.db`.

Pour utiliser une autre base de données, surchargez `DATABASE_URL` dans un
fichier `.env.local` :

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/catapi?serverVersion=8.0.32&charset=utf8mb4"
```

## Lancer l'application

Avec Symfony CLI :

```bash
symfony server:start
```

Ou avec le serveur PHP intégré :

```bash
php -S 127.0.0.1:8000 -t public
```

L'application est alors accessible à l'adresse
<http://127.0.0.1:8000>.

## API Platform

Les routes API sont préfixées par `/api`. Une fois le serveur lancé,
la documentation interactive est disponible à l'adresse :

<http://127.0.0.1:8000/api/docs>

Les ressources métier devront être ajoutées dans `src/Entity` ou
`src/ApiResource`, selon le modèle retenu, puis exposées avec les attributs
API Platform.

## Commandes utiles

```bash
# Afficher les routes disponibles
php bin/console debug:router

# Vérifier la configuration Doctrine
php bin/console doctrine:schema:validate

# Créer une migration après l'ajout d'une entité
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear
```

## Structure du projet

```text
CatAPI/
├── config/          Configuration Symfony, Doctrine et API Platform
├── migrations/      Migrations Doctrine
├── public/          Point d'entrée HTTP de l'application
├── src/
│   ├── ApiResource/ Ressources API Platform
│   ├── Controller/  Contrôleurs Symfony
│   ├── Entity/      Entités Doctrine
│   └── Repository/  Repositories Doctrine
├── templates/       Templates Twig
├── .env             Variables d'environnement par défaut
└── composer.json    Dépendances et scripts du projet
```

## Entity Relationship Diagram

![alt text](image.png)

## Configuration

Les secrets et les paramètres propres à une machine ne doivent pas être
ajoutés dans les fichiers versionnés. Utilisez `.env.local` pour les
surcharger localement, notamment pour :

- `APP_SECRET`
- `DATABASE_URL`
- `CORS_ALLOW_ORIGIN`

## Licence

Aucune licence open source n'est actuellement déclarée dans le projet.
