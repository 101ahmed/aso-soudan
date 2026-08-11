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

## Production (Render)

Dans **Environment** du Web Service :

```env
FRONTEND_URL=https://aso-soudan.onrender.com
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@votre-domaine.fr
MAIL_FROM_NAME=Rabta ACS Rennes
```

Fournisseurs simples : **Resend**, **Mailgun**, **Brevo**, SMTP OVH/Hostinger.

Sans SMTP réel (`MAIL_MAILER=log`), l’API répond OK mais **aucun email n’arrive** en boîte.

## Endpoints

| Méthode | Route | Corps |
|---|---|---|
| POST | `/api/auth/forgot-password` | `{ "email": "..." }` |
| POST | `/api/auth/reset-password` | `{ "email", "token", "password", "password_confirmation" }` |
