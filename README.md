# Decorator App — Relevé de Notes Étudiant

Projet académique réalisé dans le cadre du cours de Patrons de Conception. L'objectif est de démontrer de façon concrète et interactive le **Design Pattern Décorateur** (Decorator), en particulier sa capacité à empiler dynamiquement des comportements à l'exécution (Runtime Decoration).

L'application permet de consulter le relevé de notes d'un étudiant et d'activer, à la volée, différents décorateurs métiers et techniques pour enrichir progressivement la réponse retournée par le backend.

---

## Stack technique

| Couche    | Technologie                                      |
|-----------|--------------------------------------------------|
| Frontend  | React 18 + TypeScript, Vite (port 5173)          |
| Backend   | PHP 8+ Vanilla (aucun framework), Composer PSR-4 |
| Base de données | SQLite (dev) / MySQL via PDO (prod)        |
| Communication | API REST JSON, proxy Vite → PHP en dev      |

---

## Architecture du projet

```
Decorator_App/               ← Racine du monorepo
├── frontend/                ← Application React / Vite
└── backend/                 ← API PHP natif
```

### `frontend/`

```
frontend/
├── index.html                        ← Point d'entrée HTML (Vite)
├── vite.config.ts                    ← Config Vite + proxy /api → backend
├── tsconfig.json
├── package.json
└── src/
    ├── main.tsx                      ← Montage ReactDOM
    ├── App.tsx                       ← Composant racine, layout principal
    ├── types/
    │   └── index.ts                  ← Interfaces TypeScript partagées (Student, ReportData, etc.)
    ├── services/
    │   └── reportService.ts          ← Appels HTTP vers l'API (fetch natif)
    ├── hooks/
    │   └── useReportState.ts         ← État global et handlers (étudiant, décorateurs, chargement)
    └── components/
        ├── ControlPanel/
        │   ├── ControlPanel.tsx      ← Sélecteur d'étudiant + cases à cocher des décorateurs
        │   └── ControlPanel.module.css
        ├── Transcript/
        │   ├── TranscriptView.tsx    ← Affichage du relevé enrichi (notes, mention, rang, cache)
        │   └── TranscriptView.module.css
        └── LogConsole/
            ├── LogConsole.tsx        ← Terminal de logs en temps réel (si LoggingDecorator actif)
            └── LogConsole.module.css
```

### `backend/`

```
backend/
├── composer.json                     ← Autoloading PSR-4 (App\ → src/)
├── public/
│   ├── index.php                     ← Front Controller unique (toutes les requêtes passent ici)
│   └── .htaccess                     ← Réécriture Apache → index.php
├── config/
│   └── database.php                  ← Paramètres PDO (SQLite / MySQL, variables d'env)
├── database/
│   ├── schema.sql                    ← Script de création des tables et insertion des fixtures
│   ├── initialize.php                ← Initialise le fichier SQLite depuis le schéma
│   └── database.sqlite               ← Fichier SQLite (généré, ignoré par git en prod)
├── cache/                            ← Stockage fichier du CacheDecorator (.gitignore + .gitkeep)
├── logs/                             ← Fichiers de log du LoggingDecorator (.gitignore + .gitkeep)
└── src/
    ├── Database/
    │   └── DatabaseConnection.php    ← Singleton PDO partagé dans toute l'application
    ├── Http/
    │   ├── Router.php                ← Routeur HTTP minimaliste (GET/POST, réponses JSON, CORS)
    │   └── ReportController.php      ← Contrôleur : reçoit la requête, orchestre le Builder, retourne JSON
    ├── Services/
    │   └── DecoratorBuilder.php      ← Construit dynamiquement la pile de décorateurs selon la requête
    └── Decorators/                   ← Cœur du pattern — isolé dans son propre dossier
        ├── StudentReportInterface.php          ← Contrat commun (Component Interface)
        ├── ConcreteStudentReport.php           ← Composant de base : lecture BDD + latence simulée
        ├── AbstractStudentReportDecorator.php  ← Décorateur abstrait : délégation au wrappee
        ├── MentionDecorator.php                ← Métier : calcul de moyenne et attribution de mention
        ├── ClassementDecorator.php             ← Métier : rang dans la promotion (requête agrégée)
        ├── CacheDecorator.php                  ← Technique : mise en cache fichier/mémoire (TTL)
        └── LoggingDecorator.php                ← Technique : chronométrage et écriture des traces
```

---

## Le pattern Décorateur — rôle de chaque classe

Le pattern Décorateur permet d'ajouter dynamiquement des responsabilités à un objet sans modifier son code source ni recourir à l'héritage multiple. Les classes jouent les rôles suivants :

| Classe | Rôle dans le pattern |
|---|---|
| `StudentReportInterface` | **Component Interface** — définit le contrat `getReport(int $studentId): array` que toutes les classes implémentent |
| `ConcreteStudentReport` | **Concrete Component** — implémentation de base ; interroge la BDD et retourne les notes brutes |
| `AbstractStudentReportDecorator` | **Base Decorator** — maintient une référence au composant décoré (`$wrappee`) et délègue l'appel ; garantit la transparence |
| `MentionDecorator` | **Concrete Decorator** — calcule la moyenne et détermine la mention (TB / B / AB / P) avant de retourner le résultat enrichi |
| `ClassementDecorator` | **Concrete Decorator** — interroge l'ensemble de la promotion pour injecter le rang de l'étudiant |
| `CacheDecorator` | **Concrete Decorator** — intercepte l'appel, sert le cache si valide, sinon délègue et stocke le résultat |
| `LoggingDecorator` | **Concrete Decorator** — chronomètre l'appel au composant décoré et injecte les traces d'exécution |

La composition se lit de l'extérieur vers l'intérieur :

```
LoggingDecorator
  └─ CacheDecorator
       └─ ClassementDecorator
            └─ MentionDecorator
                 └─ ConcreteStudentReport  ←  appel BDD réel
```

Le `DecoratorBuilder` construit cette pile à l'exécution en fonction des décorateurs cochés par l'utilisateur dans l'interface.

---

## Démarrage rapide

Le frontend utilise actuellement des données simulées pour permettre de tester l'interface indépendamment de l'API PHP.

### Backend

```bash
cd backend
composer install
composer db:init
php -S localhost:8000 -t public
```

`composer db:init` crée les tables `promotions`, `students`, `courses` et `grades`, puis insère les quatre étudiants de démonstration et leurs notes. Le classement backend est calculé à partir des étudiants réellement présents dans la promotion.

### Frontend

```bash
cd frontend
npm install
npm run dev
```

L'interface est accessible sur `http://localhost:5173`. Le proxy Vite redirige automatiquement les appels `/api/*` vers `http://localhost:8000`.

---

## Flux d'une requête

1. L'utilisateur sélectionne un étudiant et coche les décorateurs souhaités.
2. Le frontend envoie un `POST /api/report` avec `{ studentId, decorators: [...] }`.
3. Le `Router` PHP dispatche vers `ReportController`.
4. Le `DecoratorBuilder` enveloppe `ConcreteStudentReport` dans la pile de décorateurs.
5. L'appel `getReport()` traverse la pile de l'extérieur vers l'intérieur, chaque décorateur enrichissant le résultat à son retour.
6. La réponse JSON enrichie est retournée au frontend qui met à jour les trois panneaux d'affichage.
