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

## Production (Render) — MailerSend

Dans le Web Service → **Environment**, ajoutez :

| Variable | Valeur |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_SCHEME` | `smtp` |
| `MAIL_HOST` | `smtp.mailersend.net` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | *(SMTP username MailerSend)* |
| `MAIL_PASSWORD` | *(SMTP password MailerSend)* |
| `MAIL_FROM_ADDRESS` | `noreply@…mlsender.net` (domaine d’essai MailerSend) |
| `MAIL_FROM_NAME` | `Rabta ACS Rennes` |
| `FRONTEND_URL` | `https://aso-soudan.onrender.com` |

Le « SMTP name » du dashboard (ex. `aso-soudan-laravel`) n’est **pas** une variable Laravel.

Sans SMTP réel (`MAIL_MAILER=log`), l’API répond OK mais **aucun email n’arrive** en boîte.

## Dépannage « le mail n’arrive pas »

1. **Le compte doit exister** dans `users` (sinon aucun email n’est envoyé, même si l’écran dit OK).  
2. Sur **Render**, les variables `MAIL_*` doivent être dans le Web Service (pas seulement en local).  
3. Regardez le dossier **Spam / Courrier indésirable**.  
4. Dans **MailerSend → Activity**, vérifiez si le message est `delivered`, `queued` ou `rejected`.  
5. Compte d’essai MailerSend : souvent seuls certains destinataires (email du compte) sont autorisés tant qu’aucun domaine n’est vérifié.  
6. `MAIL_FROM_ADDRESS` doit être sur le domaine MailerSend d’essai (ex. `…@test-….mlsender.net`).

| Méthode | Route | Corps |
|---|---|---|
| POST | `/api/auth/forgot-password` | `{ "email": "..." }` |
| POST | `/api/auth/reset-password` | `{ "email", "token", "password", "password_confirmation" }` |
