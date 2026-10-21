# Jour de la validation — liste de contrôle

## 1. Avant la séance (15 minutes)

1. Lancer **Docker Desktop** et **Ollama** (icône dans la barre des tâches).
2. Double-cliquer sur `demarrer-site.bat` : MySQL, 4 processus Laravel, la file d'attente, Apache et le préchargement de l'IA du Dépôt.
3. Ouvrir http://localhost:8090 et vérifier :
   - `php artisan depot:ia` → « Ollama est en ligne et le modèle est installé » ;
   - une analyse de photo (Dépôt), une génération d'étapes (Upcycling) et une analyse de photo (Dons) : chaque IA est ainsi chargée une fois **avant** la démonstration.
4. Facultatif : `docker compose up -d` → la version conteneurisée sur http://localhost:8095.

> Le quota gratuit de Gemini (Dons) est limité : ne pas répéter les tests IA des dons juste avant la séance.
> Si une IA ne répond pas, chaque module bascule tout seul sur sa version sans IA : la démonstration continue.

## 2. Comptes (mot de passe `password`, sauf le compte personnel)

| Compte | Rôle | Pour montrer |
|---|---|---|
| `test@example.com` | admin | back office, modération, statistiques |
| `client@example.com` | client | dépôt, réservation, profil |
| `ines@` / `youssef@` / `salma@example.com` | clients | réservations entre clients |

Données de démonstration complètes : `php artisan migrate:fresh --seed` (efface la base locale).

## 3. Ce qu'on montre, critère par critère

| Critère (points) | Où le montrer |
|---|---|
| **Gestion user (3.5)** | Inscription avec CAPTCHA puis code à 6 chiffres reçu par Gmail · « Mot de passe oublié » (lien valable 60 min) · rôles admin/client et permissions Spatie (un client reçoit 403 sur `/admin`) · double authentification par QR code et codes de secours (Mon compte → Sécurité) · profil : photo, mot de passe, suppression du compte |
| **Fonctionnalités avancées (2)** | Chaque module : voir `docs/depot-catalogue.md` (métiers simples S1–S13, avancés A1–A13) et la documentation des autres modules |
| **Features IA (3)** | Dépôt : classement d'une photo par Ollama gemma3:4b · Upcycling : génération de projet et d'étapes (qwen3:8b) · Dons : analyse de photo, rédaction de campagne, assistant (Gemini) |
| **Intégration Git (1.5)** | Branches par module, pull requests fusionnées sur `main`, historique des commits |
| **DevOps (4)** | Rapport DevOps · GitHub Actions (jobs `ci`, `docker`, `release`, `deploy`) · `docker compose ps` (4 conteneurs) |
| **Cohérence (1)** | Un vêtement du catalogue envoyé en réparation, transformé en upcycling ou donné à une association (fiche admin du vêtement) |

## 4. Commandes utiles pendant la séance

```bash
php artisan test                      # 920+ tests
php artisan depot:ia <photo>          # IA du Dépôt en direct
php artisan depot:reservations        # expiration automatique + rappels
docker compose ps                     # conteneurs
```
