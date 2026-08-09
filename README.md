# رابطة الجالية السودانية برين — RDP

**Association de la Communauté Soudanaise de Rennes**  
منصة الرابطة الرقمية المتكاملة — plateforme web administrative, académique et statistique.

## Stack (Phase 1)

| Couche | Technologie |
|---|---|
| Frontend | Vue 3 + Vite + Pinia + Vue Router + Vue I18n + Tailwind + Axios |
| Backend | Laravel 13 REST API + Sanctum |
| Base de données | MySQL 8 (WAMP / InnoDB) |
| Auth (préparé) | Laravel Sanctum |

## Structure

```text
Rabta-Digital-Platform/
├── backend/     # Laravel API
├── frontend/    # Vue.js
├── mobile/      # Flutter (futur)
├── docs/
├── database/
└── design/
```

## Prérequis

- PHP 8.3 (WAMP recommandé : `wamp64/bin/php/php8.3.6`)
- Composer
- Node.js 20+
- MySQL 8 (WAMP)
- Extension PHP `fileinfo` activée

> Sur cet environnement WAMP, le moteur par défaut MySQL était `MyISAM`. Laravel est configuré pour forcer `InnoDB`.

## Installation

### 1. Base de données

```sql
CREATE DATABASE rdp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Backend

```bash
cd backend
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8001
```

Endpoint de santé : [http://127.0.0.1:8001/api/health](http://127.0.0.1:8001/api/health)

> Le port `8000` est souvent déjà occupé ici ; RDP utilise `8001` par défaut.

### 3. Frontend

```bash
cd frontend
copy .env.example .env
npm install
npm run dev
```

Application : [http://127.0.0.1:5173](http://127.0.0.1:5173)

Page de test API : [http://127.0.0.1:5173/status](http://127.0.0.1:5173/status)

## Objectif de la phase 1

- [x] Monorepo Git
- [x] Laravel API + Sanctum
- [x] Vue 3 + Pinia + Router + I18n (FR/AR + RTL)
- [x] Tailwind CSS
- [x] MySQL connecté
- [x] CORS Frontend ↔ Backend
- [x] Endpoint `/api/health`

## État des phases

- [x] Phase 1 — Setup (API + Vue + MySQL)
- [x] Phase 2 — ERD + migrations (`database/erd.md`)
- [x] Phase 3 — Users / Roles / Permissions
- [ ] Phase 4 — Authentication complète (forgot/reset password…)

## Compte Super Admin (dev)

- Email : `admin@acs-rennes.fr`
- Mot de passe : `Password123!`
- Admin : http://127.0.0.1:5173/login

## Site public

Accueil ouvert sans login : http://127.0.0.1:5173/  
Login interne uniquement : http://127.0.0.1:5173/login

## Mise en ligne

**Recommandé (sans domaine) — Render** (URL `*.onrender.com`) :

→ [`docs/deploy-render.md`](docs/deploy-render.md)

Fichiers : `Dockerfile`, `render.yaml`, `docker/`

Hébergement mutualisé cPanel (Hostinger / o2switch…) :

→ [`docs/deploy.md`](docs/deploy.md)

Build local SPA → `backend/public` (mutualisé) :

```bash
cd frontend
copy .env.production.example .env.production
npm run build:deploy
```

## Prochaine étape

Phase 4 : Reset password réel (email) + dashboards par rôle + API des formulaires publics.
