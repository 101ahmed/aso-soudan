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

## 2. `APP_KEY` (recommandé, plus obligatoire au 1er boot)

Si `APP_KEY` est vide, le conteneur génère une clé temporaire au démarrage (le site peut démarrer).  
Pour éviter de casser les sessions à chaque redéploiement, définissez une clé fixe :

```bash
cd backend
php artisan key:generate --show
```

Puis Render → **rdp-web** → **Environment** → `APP_KEY` = `base64:...` → Save → Redeploy.  
Ne mettez **pas** de guillemets autour de la valeur.

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
4. Appliquer / Create

Au démarrage, le conteneur **seed** toujours rôles + Super Admin (sauf `SKIP_SEEDERS=true`) :

- Email : `ADMIN_EMAIL` (dashboard Render uniquement)
- Mot de passe : `ADMIN_PASSWORD` (dashboard Render uniquement, jamais dans git)

`rdp:ensure-admin` **ne réécrit plus** le mot de passe d’un admin déjà existant. Changez-le depuis l’écran Utilisateurs après la première connexion.

---

## Variables DB obligatoires (Render)

Dans le **Web Service** → **Environment** :

| Variable | Valeur |
|---|---|
| `DB_CONNECTION` | `pgsql` |
| `DB_SSLMODE` | `require` |
| `DB_URL` | voir ci-dessous |

### Quelle URL Postgres utiliser ?

1. Ouvrez la base **PostgreSQL** dans Render  
2. Section **Connections**

| URL | Quand l’utiliser |
|---|---|
| **Internal Database URL** | Web + DB dans la **même région** (ex. Frankfurt + Frankfurt) |
| **External Database URL** | Si erreur `could not translate host name "dpg-..."` → **utilisez celle-ci** |

Collez l’URL choisie dans `DB_URL` (ou `DATABASE_URL`), **Save**, redeploy.

### Erreur DNS `could not translate host name "dpg-…"`

Cause : Render lie la base avec l’**Internal Database URL** (`dpg-…-a`). Ce nom ne se résout que si le web et Postgres sont **dans la même région**. Un web en Oregon + une base à Francfort produit exactement cette erreur, et le lien Render **réécrit** `DB_URL` à chaque deploy.

L’entrypoint tente alors les hôtes publics `{id}.{région}-postgres.render.com` (Francfort en premier).

**Correctif dashboard (immédiat, sans attendre un nouveau build) :**
1. **rdp-db** → **Connect** → copier **External Database URL**
2. **rdp-web** → **Environment**
   - Si `DB_URL` est marqué « from database », **Unlink** la base puis coller l’URL externe
   - `DB_SSLMODE=require`
3. Save + **Manual Deploy**

Si **rdp-db** est `Expired` (plan gratuit, 30 jours), **Upgrade** d’abord.

---

## 6. Email (mot de passe oublié) — MailerSend **API**

Sur Render free, **SMTP port 587 time out** souvent. Utilisez l’API HTTP :

| Variable | Valeur |
|---|---|
| `MAIL_MAILER` | `mailersend` |
| `MAILERSEND_API_KEY` | *(token API MailerSend, pas le mot de passe SMTP)* |
| `MAIL_FROM_ADDRESS` | `noreply@test-….mlsender.net` |
| `MAIL_FROM_NAME` | `Rabta ACS Rennes` |
| `CONTACT_EMAIL` | `hima171221@gmail.com` |
| `FRONTEND_URL` | `https://aso-soudan.onrender.com` |
| `APP_URL` | `https://aso-soudan.onrender.com` |

Puis **Save** → **Manual Deploy**. Vérifiez `/api/health` → `mail_mailer` = **`mailersend`**.

Voir `docs/password-reset.md`.

## 7. Checklist de validation

| # | Test | Attendu |
|---|---|---|
| 1 | `https://VOTRE-SERVICE.onrender.com/` | Accueil public |
| 2 | `…/api/health` | `{"status":"ok",…,"mail_mailer":"mailersend"}` |
| 3 | `…/about` + refresh | Page من نحن (pas 404) |
| 4 | `…/login` | Connexion admin |
| 5 | Changer le mot de passe admin | OK |
| 6 | `/forgot-password` | Email reçu (Spam si besoin) |

**Note free tier :** le service s’endort après inactivité (~15 min) ; le premier chargement peut prendre 30–60 s.

---

## 8. Déploiement manuel (sans Blueprint)

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

## 9. Domaine personnalisé (plus tard)

Render → service → **Custom Domains** → ajouter `acs-rennes.fr`  
Puis définir éventuellement `APP_URL` / `FRONTEND_URL` en dur sur la nouvelle URL.

---

## 10. Limites à connaître

- Base **PostgreSQL** (pas MySQL WAMP) — les migrations Laravel du projet sont compatibles.
- Disque éphémère : fichiers uploadés dans `storage` peuvent être perdus au redeploy (OK pour la phase actuelle).
- Pour la prod associative durable : plan payant ou autre hébergeur + domaine.
