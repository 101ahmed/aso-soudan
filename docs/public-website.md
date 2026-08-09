# Site public vs système interne

## Règle

- Contenu public → sans login
- Données admin / académiques personnelles → login obligatoire
- Modification de contenu / rapports détaillés → login + permission

## URL publiques

- `/` accueil
- `/about` `/president` `/secretariats` `/shura-council` `/parents-council`
- `/news` `/events` `/gallery`
- `/register/student` `/register/member`
- `/contact`
- `/login` (comptes internes uniquement)

## Login

Après authentification, redirection selon le rôle (`resolvePostLoginPath`).
