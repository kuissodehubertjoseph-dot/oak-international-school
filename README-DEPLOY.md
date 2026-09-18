# Déploiement sur Railway

Ce site (PHP 8.3 + SQLite) est prêt à être déployé sur Railway via le `Dockerfile` fourni.

## Ce qui a été préparé

- `Dockerfile` — image PHP 8.3 + Apache + extension SQLite.
- `docker-entrypoint.sh` — adapte le port au démarrage (Railway l'attribue dynamiquement) et prépare les dossiers `data/` et `uploads/`.
- `includes/db.php` — le chemin de la base SQLite est configurable via la variable d'environnement `DB_PATH` (sinon `data/leselites.sqlite` par défaut).
- `.gitignore` / `.dockerignore` — excluent `data/`, `uploads/` et `backups/` du dépôt et de l'image : ces dossiers contiennent des données sensibles (mots de passe admin hashés, dossiers d'élèves) qui ne doivent jamais être poussées sur GitHub.

**Important** : comme `data/` n'est pas inclus dans l'image, le tout premier démarrage crée automatiquement une base vide avec un compte admin par défaut :
- Email : `admin@leselites.bj`
- Mot de passe : `LesElites2026!`

**Changez ce mot de passe immédiatement après le premier déploiement.**

## Étapes à suivre (depuis votre terminal)

### 1. Initialiser git (si ce n'est pas déjà fait)
```bash
git init
git add .
git commit -m "Préparation du déploiement Railway"
```

### 2. Installer la CLI Railway
```bash
npm install -g @railway/cli
```

### 3. Se connecter et déployer
```bash
railway login
railway init
railway up
```
`railway login` ouvre votre navigateur pour vous authentifier — je ne peux pas le faire à votre place.

### 4. Ajouter des volumes persistants (dans le dashboard Railway)
Sans ça, la base de données et les fichiers uploadés seraient perdus à chaque redéploiement.

Dans le dashboard du service (Settings → Volumes), ajoutez :
- Un volume monté sur `/var/www/html/data`
- Un volume monté sur `/var/www/html/uploads`

### 5. Générer un domaine public
Dans Settings → Networking → **Generate Domain**.

### 6. Vérifier
- Ouvrez l'URL générée : le site doit s'afficher normalement.
- Connectez-vous sur `/login.php` avec les identifiants par défaut ci-dessus, puis changez le mot de passe dans l'admin.

## Limite connue

Les images ajoutées via le panneau d'administration (galerie, staff, etc.) sont écrites dans `images/`, qui fait partie du code déployé et **n'est pas persistant** : un redéploiement (nouveau `git push` / `railway up`) réinitialise ce dossier à son contenu du dépôt git. Pour l'instant, ajoutez les nouvelles images via le code (comme on l'a fait dans cette conversation) plutôt que via l'admin en production. Si vous voulez que les uploads d'images survivent aux redéploiements, il faudra soit ajouter un troisième volume sur `images/`, soit migrer vers un stockage externe (Cloudinary, S3) — dites-le-moi si vous voulez que je mette ça en place.
