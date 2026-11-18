# Backend : API PHP du projet Decorator

API REST JSON en PHP 8.1+ (sans framework externe), respectant le standard PSR-4, base de donnees SQLite pour le developpement et support MySQL pour la production.

Elle renvoie le releve de notes d'un etudiant, enrichi dynamiquement a l'execution par differents decorateurs (Design Pattern Decorateur) selectionnes par le client.

## Demarrage rapide

```bash
cd backend
composer install
composer db:init            # cree database/database.sqlite avec 4 etudiants de test
composer serve              # demarre le serveur : php -S localhost:8000 -t public
composer test               # lance les 31 tests unitaires en memoire
```

Sous Windows, dans votre fichier `php.ini`, assurez-vous que `extension=pdo_sqlite` et `extension=sqlite3` sont bien actives (sans point-virgule devant).

Variables d'environnement disponibles (facultatives) :
- `SIMULATED_LATENCY_MS` : latence simulee (2000 ms par defaut, definir a 0 pour un developpement rapide).
- `CACHE_TTL` : duree de validite du cache en secondes (300 par defaut).
- `CORS_ORIGIN` : domaine frontend autorise (`http://localhost:5173` par defaut).
- `APP_DEBUG` : affichage des erreurs d'exception (1 pour le debug, 0 en production).
- `DB_DRIVER` : choix du moteur de base de donnees (`sqlite` ou `mysql`).

Exemple sous PowerShell :
```powershell
$env:SIMULATED_LATENCY_MS = "0"
composer serve
```

## Le Design Pattern Decorateur applique

Ce projet implemente rigoureusement le Design Pattern Decorateur selon les standards GoF (Gang of Four) :

1. **StudentReportInterface (Component Interface)** : Le contrat commun que le client consomme.
2. **ConcreteStudentReport (Concrete Component)** : Le composant de base qui interroge la base de donnees et extrait les notes brutes.
3. **AbstractStudentReportDecorator (Base Decorator)** : La classe abstraite enveloppant une instance de StudentReportInterface.
4. **Decorateurs metier concrets** :
   - `MentionDecorator` : calcule la moyenne ponderee et attribue la mention (TB, B, AB, P, NV).
   - `ClassementDecorator` : calcule le rang dans la promotion et l'effectif total par requete agregee independante.
5. **Decorateurs techniques concrets** :
   - `CacheDecorator` : intercepte les requetes et sert les donnees depuis des fichiers JSON pour un acces quasi instantane.
   - `LoggingDecorator` : chronometre l'execution et trace chaque passage dans la chaine de traitement.

### Pile d'execution et ordre des couches

- Ordre par defaut (de l'exterieur vers l'interieur) : `LoggingDecorator > CacheDecorator > ClassementDecorator > MentionDecorator > ConcreteStudentReport`.
- Option `cacheOutermost: true` : `CacheDecorator > LoggingDecorator > ...` (un HIT de cache court-circuite le logging).
- La cle de cache associe l'identifiant etudiant et la combinaison active des decorateurs metier, garantissant l'absence de resultats perimes.

## Routes de l'API REST

Toutes les reponses sont formatees en JSON UTF-8.

- `GET /api/health` : verification du statut du serveur (`{ "status": "ok" }`).
- `GET /api/students` : liste des etudiants (`{ "students": [ { "id", "name", "promotion" } ] }`).
- `POST /api/report` : generation d'un releve de notes enrichi.
- `POST /api/cache/clear` : purge du cache (`{ "cleared": nombre }`).

### Exemple de requete POST /api/report

```json
{
  "studentId": 3,
  "decorators": ["mention", "classement", "cache", "logging"],
  "options": {
    "cacheOutermost": false
  }
}
```

## Tests automatises

Les tests unitaires et d'integration sont regroupes dans `tests/run.php`. Ils s'executent sans aucune dependance externe a l'aide d'une base SQLite en memoire :
```bash
php tests/run.php
```
