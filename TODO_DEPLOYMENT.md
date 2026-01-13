# Checklist de Configuration CI/CD - Ressurex Platform

## 📋 Statut Actuel

- ✅ Backend : Repository créé
- ✅ Backend : Workflow CI/CD créé (branche `backoffice`)
- ✅ Backend : Workflow mis à jour pour utiliser `DOKPLOY_WEBHOOK_URL_BACKEND`
- ⏳ Frontend : À créer
- ⏳ Secrets GitHub : À configurer
- ⏳ Applications Dokploy : À créer

---

## 🎯 Actions à Réaliser

### 1️⃣ Configuration GitHub - Backend

#### A. Créer le Personal Access Token

- [ ] Aller sur GitHub → Settings → Developer settings → Personal access tokens
- [ ] Créer un nouveau token avec les permissions :
  - `repo` (Full control)
  - `write:packages`
  - `read:packages`
- [ ] Copier le token (format : `ghp_xxxxxxxxxxxx`)

#### B. Configurer les secrets du repository Backend

- [ ] Aller sur le repository `ressurex-backend` → Settings → Secrets → Actions
- [ ] Ajouter le secret `GH_TOKEN` avec le token créé
- [ ] Ajouter le secret `DOKPLOY_WEBHOOK_URL_BACKEND` (URL à récupérer de Dokploy)

### 2️⃣ Configuration Dokploy - Backend

#### A. Créer l'application Backend

- [ ] Se connecter à Dokploy
- [ ] Créer une nouvelle application :
  - **Nom** : `ressurex-backend`
  - **Type** : Docker
  - **Source** : GitHub Container Registry
  - **Image** : `ghcr.io/votre-org/ressurex-backend:latest`

#### B. Configurer l'application Backend

- [ ] Configurer les variables d'environnement :
  ```env
  APP_NAME=Ressurex
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://api.ressurex.com
  DB_CONNECTION=mysql
  DB_HOST=mysql
  DB_PORT=3306
  DB_DATABASE=ressurex
  DB_USERNAME=ressurex_user
  DB_PASSWORD=********
  ```
- [ ] Configurer le port interne : `8080`
- [ ] Configurer le domaine : `api.ressurex.com`
- [ ] Activer SSL/TLS (Let's Encrypt)

#### C. Configurer Registry credentials

- [ ] Aller dans Settings → Registry credentials
- [ ] Ajouter les credentials :
  - **Registry** : `ghcr.io`
  - **Username** : Votre nom d'utilisateur GitHub
  - **Password** : Le token `GH_TOKEN`

#### D. Récupérer le webhook

- [ ] Aller dans l'onglet "Webhooks" de l'application
- [ ] Copier l'URL du webhook : `https://your-dokploy.com/api/webhook/xxx-backend-xxx`
- [ ] Ajouter cette URL comme secret `DOKPLOY_WEBHOOK_URL_BACKEND` dans GitHub

### 3️⃣ Test du Déploiement Backend

- [ ] Faire un push sur la branche `backoffice` :
  ```bash
  cd ressurex-backend
  git add .
  git commit -m "test: trigger CI/CD"
  git push origin backoffice
  ```
- [ ] Vérifier que le workflow GitHub Actions s'exécute sans erreur
- [ ] Vérifier que l'image est publiée sur GitHub Container Registry
- [ ] Vérifier que Dokploy a reçu le webhook et redémarré l'application
- [ ] Tester l'API : `curl https://api.ressurex.com/api/health`

---

### 4️⃣ Création du Frontend

#### A. Cloner et configurer le repository

- [ ] Cloner le frontend ECJUEMOA :
  ```bash
  git clone https://github.com/votre-org/ecjuemoa-project-frontend.git ressurex-frontend
  cd ressurex-frontend
  ```
- [ ] Changer le remote :
  ```bash
  git remote remove origin
  git remote add origin https://github.com/votre-org/ressurex-frontend.git
  ```
- [ ] Créer la branche `backoffice` :
  ```bash
  git checkout -b backoffice
  ```

#### B. Mettre à jour les fichiers

- [ ] Mettre à jour `package.json` :
  - Changer `"name": "vex"` en `"name": "ressurex"`
- [ ] Créer le dossier `.github/workflows` :
  ```bash
  mkdir -p .github/workflows
  ```
- [ ] Copier le workflow depuis `FRONTEND_WORKFLOW_TEMPLATE.md` dans `.github/workflows/ci.yaml`
- [ ] Mettre à jour les URLs d'API dans les fichiers de configuration

#### C. Commiter et pousser

- [ ] Commiter les changements :
  ```bash
  git add .
  git commit -m "chore: setup Ressurex frontend CI/CD"
  git push -u origin backoffice
  ```

### 5️⃣ Configuration GitHub - Frontend

- [ ] Aller sur le repository `ressurex-frontend` → Settings → Secrets → Actions
- [ ] Ajouter le secret `GH_TOKEN` (même token que le backend)
- [ ] Ajouter le secret `DOKPLOY_WEBHOOK_URL_FRONTEND` (URL à récupérer de Dokploy)

### 6️⃣ Configuration Dokploy - Frontend

#### A. Créer l'application Frontend

- [ ] Se connecter à Dokploy
- [ ] Créer une nouvelle application :
  - **Nom** : `ressurex-frontend`
  - **Type** : Docker
  - **Source** : GitHub Container Registry
  - **Image** : `ghcr.io/votre-org/ressurex-frontend:latest`

#### B. Configurer l'application Frontend

- [ ] Configurer les variables d'environnement :
  ```env
  API_URL=https://api.ressurex.com
  ```
- [ ] Configurer le port interne : `80`
- [ ] Configurer le domaine : `app.ressurex.com`
- [ ] Activer SSL/TLS (Let's Encrypt)

#### C. Configurer Registry credentials

- [ ] Aller dans Settings → Registry credentials
- [ ] Ajouter les credentials :
  - **Registry** : `ghcr.io`
  - **Username** : Votre nom d'utilisateur GitHub
  - **Password** : Le token `GH_TOKEN`

#### D. Récupérer le webhook

- [ ] Aller dans l'onglet "Webhooks" de l'application
- [ ] Copier l'URL du webhook : `https://your-dokploy.com/api/webhook/yyy-frontend-yyy`
- [ ] Ajouter cette URL comme secret `DOKPLOY_WEBHOOK_URL_FRONTEND` dans GitHub

### 7️⃣ Test du Déploiement Frontend

- [ ] Faire un push sur la branche `backoffice` :
  ```bash
  cd ressurex-frontend
  git add .
  git commit -m "test: trigger CI/CD"
  git push origin backoffice
  ```
- [ ] Vérifier que le workflow GitHub Actions s'exécute sans erreur
- [ ] Vérifier que l'image est publiée sur GitHub Container Registry
- [ ] Vérifier que Dokploy a reçu le webhook et redémarré l'application
- [ ] Tester l'application : `curl -I https://app.ressurex.com`

---

## ✅ Validation Finale

### Backend

- [ ] L'API répond correctement : `curl https://api.ressurex.com/api/health`
- [ ] Les logs Dokploy ne montrent pas d'erreurs
- [ ] Le certificat SSL est valide
- [ ] Les variables d'environnement sont correctement chargées

### Frontend

- [ ] L'application est accessible : `https://app.ressurex.com`
- [ ] L'application charge correctement
- [ ] L'application peut communiquer avec le backend
- [ ] Le certificat SSL est valide

### CI/CD

- [ ] Les workflows GitHub Actions sont verts ✅
- [ ] Les images Docker sont publiées sur GHCR
- [ ] Les webhooks Dokploy fonctionnent correctement
- [ ] Les déploiements automatiques fonctionnent après un push sur `backoffice`

---

## 🚨 Problèmes Courants

### Le workflow échoue avec "Permission denied"

**Solution** : Vérifiez que le token `GH_TOKEN` a les bonnes permissions (`write:packages`)

### L'image n'est pas téléchargée par Dokploy

**Solution** : Vérifiez les credentials du registry dans Dokploy (ghcr.io)

### Le webhook retourne une erreur 404

**Solution** : Vérifiez l'URL du webhook dans Dokploy, elle doit être exacte

### L'application ne démarre pas dans Dokploy

**Solution** : Vérifiez les logs Dokploy et assurez-vous que toutes les variables d'environnement sont configurées

---

## 📚 Documentation

- [DEPLOYMENT.md](./DEPLOYMENT.md) - Guide complet de déploiement
- [FRONTEND_WORKFLOW_TEMPLATE.md](./FRONTEND_WORKFLOW_TEMPLATE.md) - Template pour le workflow frontend
- [.github/workflows/ci.yaml](./.github/workflows/ci.yaml) - Workflow CI/CD backend

---

## 🔄 Récapitulatif des Secrets

| Repository | Secret | Valeur |
|------------|--------|--------|
| **ressurex-backend** | `GH_TOKEN` | Token GitHub avec permissions packages |
| **ressurex-backend** | `DOKPLOY_WEBHOOK_URL_BACKEND` | `https://dokploy.com/api/webhook/xxx-backend-xxx` |
| **ressurex-frontend** | `GH_TOKEN` | Token GitHub avec permissions packages (même) |
| **ressurex-frontend** | `DOKPLOY_WEBHOOK_URL_FRONTEND` | `https://dokploy.com/api/webhook/yyy-frontend-yyy` |

---

## 🎯 Ordre d'Exécution Recommandé

1. ✅ Backend : Workflow déjà créé
2. ⏳ Créer le token GitHub PAT
3. ⏳ Configurer l'application Dokploy Backend
4. ⏳ Ajouter les secrets GitHub Backend
5. ⏳ Tester le déploiement Backend
6. ⏳ Créer le repository Frontend
7. ⏳ Configurer l'application Dokploy Frontend
8. ⏳ Ajouter les secrets GitHub Frontend
9. ⏳ Tester le déploiement Frontend
10. ⏳ Validation finale des deux applications

---

**Date de création** : 8 janvier 2026
**Statut** : En attente de configuration
