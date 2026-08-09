# Déploiement RDP sur Render

URL publique gratuite du type : `https://rdp-web-xxxx.onrender.com`  
(pas besoin d’acheter un nom de domaine)

Architecture : **un seul service Docker** (Vue SPA + API Laravel `/api`) + **PostgreSQL** Render.

---

## 1. Prérequis

- Compte [Render](https://render.com)
- Dépôt Git (GitHub / GitLab) contenant ce monorepo
- En local : PHP 8.3 pour générer `APP_KEY`

---

## 2. Générer `APP_KEY` (local)

```bash
cd backend
php artisan key:generate --show
```

Copiez la valeur (`base64:...`) — elle sera collée dans Render.

---

## 3. Pousser le code

Committez puis poussez vers GitHub (fichiers utiles déjà dans le repo) :

- `Dockerfile`
- `render.yaml`
- `docker/entrypoint.sh`
- `docker/apache-*.conf`

---

## 4. Créer le Blueprint sur Render

1. [Dashboard Render](https://dashboard.render.com) → **New** → **Blueprint**
2. Connecter le dépôt Git
3. Render lit `render.yaml` et propose :
   - base **rdp-db** (PostgreSQL free)
   - service **rdp-web** (Docker free)
4. Pour la variable **`APP_KEY`** (sync: false) : coller la clé générée à l’étape 2
5. Appliquer / Create

Au premier démarrage, `RUN_SEEDERS=true` crée le Super Admin :

- Email : `admin@acs-rennes.fr`
- Mot de passe : `Password123!` → **à changer immédiatement**

Puis dans Render → Environment : mettre `RUN_SEEDERS=false` et redéployer (évite de reseeder à chaque restart).

---

## 5. Variables automatiques

L’entrypoint utilise :

| Variable Render | Usage Laravel |
|---|---|
| `RENDER_EXTERNAL_URL` | `APP_URL` + `FRONTEND_URL` |
| `RENDER_EXTERNAL_HOSTNAME` | `SANCTUM_STATEFUL_DOMAINS` |
| `DB_URL` (depuis Postgres) | connexion `pgsql` |
| `PORT` | Apache |

---

## 6. Checklist de validation

| # | Test | Attendu |
|---|---|---|
| 1 | `https://VOTRE-SERVICE.onrender.com/` | Accueil public |
| 2 | `…/api/health` | `{"status":"ok",…,"database":"ok"}` |
| 3 | `…/about` + refresh | Page من نحن (pas 404) |
| 4 | `…/login` | Connexion admin |
| 5 | Changer le mot de passe admin | OK |

**Note free tier :** le service s’endort après inactivité (~15 min) ; le premier chargement peut prendre 30–60 s.

---

## 7. Déploiement manuel (sans Blueprint)

1. **New PostgreSQL** → noter Internal Database URL  
2. **New Web Service** → Docker, racine du repo, `Dockerfile`  
3. Env :
   - `APP_KEY` = `base64:…`
   - `APP_ENV=production` / `APP_DEBUG=false`
   - `DB_CONNECTION=pgsql`
   - `DB_URL` = lien Postgres (ou « Link database »)
   - `DB_SSLMODE=require`
   - `RUN_SEEDERS=true` (1ère fois)
4. Health check path : `/api/health`

---

## 8. Domaine personnalisé (plus tard)

Render → service → **Custom Domains** → ajouter `acs-rennes.fr`  
Puis définir éventuellement `APP_URL` / `FRONTEND_URL` en dur sur la nouvelle URL.

---

## 9. Limites à connaître

- Base **PostgreSQL** (pas MySQL WAMP) — les migrations Laravel du projet sont compatibles.
- Disque éphémère : fichiers uploadés dans `storage` peuvent être perdus au redeploy (OK pour la phase actuelle).
- Pour la prod associative durable : plan payant ou autre hébergeur + domaine.
