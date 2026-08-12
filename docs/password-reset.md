# Réinitialisation du mot de passe (forgot / reset)

## Flux

1. Utilisateur → `/forgot-password` → saisit son email  
2. API `POST /api/auth/forgot-password` → Laravel envoie un email avec lien  
3. Lien : `{FRONTEND_URL}/reset-password?token=...&email=...`  
4. Utilisateur choisit un nouveau mot de passe → `POST /api/auth/reset-password`  
5. Tous les tokens Sanctum de l’utilisateur sont révoqués

## Local (WAMP)

Dans `backend/.env` :

```env
MAIL_MAILER=log
FRONTEND_URL=http://127.0.0.1:5173
```

Les emails sont écrits dans `backend/storage/logs/laravel.log` (chercher le lien `reset-password`).

Pour tester une vraie boîte en local :

```env
MAIL_MAILER=mailersend
MAILERSEND_API_KEY=mlsn.xxxxx
MAIL_FROM_ADDRESS=noreply@test-….mlsender.net
MAIL_FROM_NAME="Rabta ACS Rennes"
```

## Production (Render) — MailerSend **API** (pas SMTP)

Sur le **free tier Render**, le SMTP (`smtp.mailersend.net:587`) **time out** souvent (ports SMTP filtrés).  
Utilisez l’**API HTTP** MailerSend (port 443) :

| Variable | Valeur |
|---|---|
| `MAIL_MAILER` | `mailersend` |
| `MAILERSEND_API_KEY` | *(API token MailerSend → Settings → API tokens)* |
| `MAIL_FROM_ADDRESS` | `noreply@…mlsender.net` (domaine d’essai) |
| `MAIL_FROM_NAME` | `Rabta ACS Rennes` |
| `FRONTEND_URL` | `https://aso-soudan.onrender.com` |
| `APP_URL` | `https://aso-soudan.onrender.com` |

Le « SMTP name » / user `MS_…@….mlsender.net` **ne remplace pas** `MAILERSEND_API_KEY`.

Vérifiez `/api/health` → `"mail_mailer":"mailersend"`.

## Dépannage « le mail n’arrive pas »

1. **Le compte doit exister** dans `users` (sinon aucun email, même si l’écran dit OK).  
2. Sur Render : `MAIL_MAILER=mailersend` + `MAILERSEND_API_KEY` (pas seulement SMTP local).  
3. Erreur `Unable to connect to smtp.mailersend.net:587` → passez à l’API (`mailersend`).  
4. Message « trop de temps à répondre » : souvent SMTP qui bloque ~60 s, ou réveil Render — réessayez après `/api/health`.  
5. Spam / Courrier indésirable.  
6. MailerSend → **Activity** : `delivered` / `rejected`.  
7. Compte d’essai : destinataires souvent limités (email du compte MailerSend) tant qu’aucun domaine n’est vérifié.

| Méthode | Route | Corps |
|---|---|---|
| POST | `/api/auth/forgot-password` | `{ "email": "..." }` |
| POST | `/api/auth/reset-password` | `{ "email", "token", "password", "password_confirmation" }` |
