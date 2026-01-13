# Guide de Déploiement CI/CD - Ressurex Platform

## 📋 Table des matières

- [Architecture de déploiement](#architecture-de-déploiement)
- [Prérequis](#prérequis)
- [Configuration GitHub](#configuration-github)
- [Configuration Dokploy](#configuration-dokploy)
- [Workflows CI/CD](#workflows-cicd)
- [Déploiement](#déploiement)
- [Dépannage](#dépannage)

---

## 🏗️ Architecture de déploiement

Le projet Ressurex est composé de deux applications distinctes :

- **Backend** : API Laravel (PHP 8.3)
- **Frontend** : Application Angular 17 (à créer - clone de ecjuemoa-project-frontend)

Chaque application dispose de :
- Son propre repository GitHub
- Son propre workflow CI/CD
- Son propre container Docker
- Son propre webhook Dokploy

```
┌─────────────────────────────────────────────────────────────┐
│                     GitHub Repositories                      │
├──────────────────────────┬──────────────────────────────────┤
│   Backend Repository     │    Frontend Repository           │
│   (Laravel API)          │    (Angular App)                 │
│   Branch: backoffice     │    Branch: backoffice            │
└──────────┬───────────────┴──────────────┬───────────────────┘
           │                               │
           │ Push to backoffice            │ Push to backoffice
           │                               │
           ▼                               ▼
┌──────────────────────────┐    ┌──────────────────────────┐
│  GitHub Actions (Backend)│    │ GitHub Actions (Frontend)│
│  - Build Docker Image    │    │ - Build Angular App      │
│  - Push to GHCR          │    │ - Build Docker Image     │
│  - Trigger Webhook       │    │ - Push to GHCR           │
└──────────┬───────────────┘    └───────────┬──────────────┘
           │                                 │
           │ Webhook Backend                 │ Webhook Frontend
           │                                 │
           ▼                                 ▼
┌──────────────────────────┐    ┌──────────────────────────┐
│  Dokploy App (Backend)   │    │  Dokploy App (Frontend)  │
│  - Pull new image        │    │  - Pull new image        │
│  - Restart container     │    │  - Restart container     │
└──────────────────────────┘    └──────────────────────────┘
```

---

## ✅ Prérequis

### 1. GitHub

- Accès admin aux repositories backend et frontend
- Token GitHub avec permissions `packages:write` et `contents:read`

### 2. Dokploy

- Instance Dokploy installée et accessible
- Deux applications créées dans Dokploy :
  - Application Backend (Laravel)
  - Application Frontend (Angular)
- URL de webhook pour chaque application

### 3. Docker

- Les Dockerfiles doivent être présents :
  - Backend : `./production/Dockerfile`
  - Frontend : `./Dockerfile`

---

## 🔧 Configuration GitHub

### Étape 1 : Créer un Personal Access Token (PAT)

1. Allez sur GitHub → **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)**
2. Cliquez sur **Generate new token** → **Generate new token (classic)**
3. Donnez un nom descriptif : `Ressurex CI/CD Token`
4. Sélectionnez les scopes suivants :
   - ✅ `repo` (Full control of private repositories)
   - ✅ `write:packages` (Upload packages to GitHub Package Registry)
   - ✅ `read:packages` (Download packages from GitHub Package Registry)
5. Cliquez sur **Generate token**
6. **Copiez le token immédiatement** (vous ne pourrez plus le voir)

### Étape 2 : Configurer les secrets dans le repository Backend

1. Allez sur le repository **ressurex-backend** → **Settings** → **Secrets and variables** → **Actions**
2. Cliquez sur **New repository secret**
3. Ajoutez les secrets suivants :

| Nom du secret | Valeur | Description |
|---------------|--------|-------------|
| `GH_TOKEN` | `ghp_xxxxxxxxxxxxx` | Token GitHub créé à l'étape 1 |
| `DOKPLOY_WEBHOOK_URL_BACKEND` | `https://your-dokploy.com/api/webhook/xxx` | URL webhook de l'application backend dans Dokploy |

### Étape 3 : Configurer les secrets dans le repository Frontend

1. Allez sur le repository **ressurex-frontend** → **Settings** → **Secrets and variables** → **Actions**
2. Cliquez sur **New repository secret**
3. Ajoutez les secrets suivants :

| Nom du secret | Valeur | Description |
|---------------|--------|-------------|
| `GH_TOKEN` | `ghp_xxxxxxxxxxxxx` | Token GitHub créé à l'étape 1 (même token) |
| `DOKPLOY_WEBHOOK_URL_FRONTEND` | `https://your-dokploy.com/api/webhook/yyy` | URL webhook de l'application frontend dans Dokploy |

---

## 🚀 Configuration Dokploy

### Étape 1 : Créer l'application Backend

1. Connectez-vous à votre instance Dokploy
2. Créez une nouvelle application :
   - **Nom** : `ressurex-backend`
   - **Type** : Docker
   - **Source** : GitHub Container Registry
   - **Image** : `ghcr.io/votre-org/ressurex-backend:latest`
3. Configurez les variables d'environnement nécessaires :
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
   DB_PASSWORD=votre_mot_de_passe_securise

   # Autres variables...
   ```
4. Configurez le port :
   - **Port interne** : 8080
   - **Port externe** : Votre choix (ou auto)
5. Configurez le domaine :
   - Ajoutez votre domaine : `api.ressurex.com`
   - Activez SSL/TLS avec Let's Encrypt
6. **Récupérez l'URL du webhook** :
   - Dans l'onglet "Webhooks" de l'application
   - Copiez l'URL complète : `https://your-dokploy.com/api/webhook/xxx-backend-xxx`
   - Utilisez cette URL pour `DOKPLOY_WEBHOOK_URL_BACKEND` dans GitHub

### Étape 2 : Créer l'application Frontend

1. Dans Dokploy, créez une nouvelle application :
   - **Nom** : `ressurex-frontend`
   - **Type** : Docker
   - **Source** : GitHub Container Registry
   - **Image** : `ghcr.io/votre-org/ressurex-frontend:latest`
2. Configurez les variables d'environnement (si nécessaire) :
   ```env
   API_URL=https://api.ressurex.com
   ```
3. Configurez le port :
   - **Port interne** : 80
   - **Port externe** : Votre choix (ou auto)
4. Configurez le domaine :
   - Ajoutez votre domaine : `app.ressurex.com`
   - Activez SSL/TLS avec Let's Encrypt
5. **Récupérez l'URL du webhook** :
   - Dans l'onglet "Webhooks" de l'application
   - Copiez l'URL complète : `https://your-dokploy.com/api/webhook/yyy-frontend-yyy`
   - Utilisez cette URL pour `DOKPLOY_WEBHOOK_URL_FRONTEND` dans GitHub

### Étape 3 : Configurer l'authentification au GitHub Container Registry

Dans les deux applications Dokploy, configurez l'accès au GHCR :

1. Allez dans **Settings** → **Registry credentials**
2. Ajoutez les credentials :
   - **Registry URL** : `ghcr.io`
   - **Username** : Votre nom d'utilisateur GitHub
   - **Password/Token** : Le token `GH_TOKEN` créé précédemment

---

## 📝 Workflows CI/CD

### Backend Workflow (`.github/workflows/ci.yaml`)

Le workflow backend se déclenche sur un push vers la branche `backoffice` :

```yaml
name: Build and Deploy Docker Image

on:
  push:
    branches: ["backoffice"]

permissions:
  contents: read
  packages: write
  actions: read

jobs:
  build-and-push:
    runs-on: ubuntu-latest
    timeout-minutes: 30

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4
        with:
          fetch-depth: 1

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Log in to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GH_TOKEN }}

      - name: Extract metadata for Docker
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ghcr.io/${{ github.repository }}
          tags: |
            type=raw,value=latest
            type=sha,format=long
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}

      - name: Build and Push Docker Image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: ./production/Dockerfile
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
          platforms: linux/amd64
          provenance: false

      - name: Notify Dokploy via Webhook
        if: success()
        env:
          WEBHOOK_URL: ${{ secrets.DOKPLOY_WEBHOOK_URL_BACKEND }}
        run: |
          set +e
          echo "Calling webhook..."

          response=$(curl -X POST "${WEBHOOK_URL}" \
            -w "\nHTTP_CODE:%{http_code}" \
            -s \
            --max-time 30)
          curl_exit_code=$?

          echo "Curl exit code: $curl_exit_code"
          echo "Response:"
          echo "$response"

          http_code=$(echo "$response" | grep "HTTP_CODE" | cut -d: -f2)
          echo "HTTP Code: $http_code"

          if [ "$http_code" = "200" ] || [ "$http_code" = "201" ] || [ "$http_code" = "204" ]; then
            echo "✅ Webhook successful with code: $http_code"
          else
            echo "⚠️ Webhook failed with code: $http_code (curl exit: $curl_exit_code)"
            echo "Note: This will not fail the deployment as the image was successfully pushed"
          fi
        timeout-minutes: 2
```

### Frontend Workflow (`.github/workflows/ci.yaml`)

Le workflow frontend est similaire mais utilise le webhook frontend :

**Différences clés :**
```yaml
# Même branche de déclenchement
on:
  push:
    branches: ["backoffice"]

# Dans l'étape "Build and Push Docker Image"
file: ./Dockerfile  # Au lieu de ./production/Dockerfile

# Dans l'étape "Notify Dokploy via Webhook"
WEBHOOK_URL: ${{ secrets.DOKPLOY_WEBHOOK_URL_FRONTEND }}  # Au lieu de BACKEND
```

---

## 🚀 Déploiement

### Déploiement automatique

Le déploiement se fait automatiquement lors d'un push vers la branche `backoffice` :

```bash
# Pour le backend
cd ressurex-backend
git add .
git commit -m "feat: nouvelle fonctionnalité backend"
git push origin backoffice

# Pour le frontend
cd ressurex-frontend
git add .
git commit -m "feat: nouvelle fonctionnalité frontend"
git push origin backoffice
```

### Flux de déploiement

1. **Push vers `backoffice`** → Déclenche le workflow GitHub Actions
2. **Build de l'image Docker** → Construction de l'image avec cache
3. **Push vers GHCR** → Publication de l'image sur GitHub Container Registry
4. **Appel du webhook** → Notification à Dokploy
5. **Pull et restart** → Dokploy télécharge la nouvelle image et redémarre le container

### Vérification du déploiement

#### Dans GitHub Actions

1. Allez sur votre repository → **Actions**
2. Sélectionnez le workflow en cours
3. Vérifiez que toutes les étapes sont vertes ✅
4. Consultez les logs pour plus de détails

#### Dans Dokploy

1. Connectez-vous à Dokploy
2. Allez sur l'application concernée
3. Consultez les logs en temps réel :
   ```bash
   # Logs backend
   docker logs -f ressurex-backend

   # Logs frontend
   docker logs -f ressurex-frontend
   ```

#### Test des applications

```bash
# Test Backend API
curl https://api.ressurex.com/api/health
# Attendu : {"status": "ok", "version": "1.0.0"}

# Test Frontend
curl https://app.ressurex.com
# Attendu : Code HTML de l'application Angular
```

---

## 🔄 Migration depuis ECJUEMOA

Si vous migrez depuis ECJUEMOA, voici les étapes à suivre :

### 1. Mise à jour du workflow Backend

Modifiez `.github/workflows/ci.yaml` :

```yaml
# Changez la branche de déclenchement
on:
  push:
    branches: ["backoffice"]  # Au lieu de "reviewcode"

# Changez le secret du webhook
env:
  WEBHOOK_URL: ${{ secrets.DOKPLOY_WEBHOOK_URL_BACKEND }}  # Au lieu de DOKPLOY_WEBHOOK_URL
```

### 2. Création du Frontend (si pas encore fait)

Clonez le repository frontend ECJUEMOA :

```bash
# Cloner le frontend ECJUEMOA
git clone https://github.com/votre-org/ecjuemoa-project-frontend.git ressurex-frontend

cd ressurex-frontend

# Changer l'origin
git remote remove origin
git remote add origin https://github.com/votre-org/ressurex-frontend.git

# Créer la branche backoffice
git checkout -b backoffice
git push -u origin backoffice
```

### 3. Mettre à jour les configurations

- Mettez à jour les noms dans `package.json`
- Mettez à jour les URLs d'API dans les fichiers de configuration
- Mettez à jour le workflow `.github/workflows/ci.yaml` pour utiliser `backoffice` et `DOKPLOY_WEBHOOK_URL_FRONTEND`

---

## 🔍 Dépannage

### Problème : Le workflow échoue à l'étape "Log in to GitHub Container Registry"

**Cause** : Token GitHub invalide ou permissions insuffisantes

**Solution** :
1. Vérifiez que le secret `GH_TOKEN` est correctement configuré
2. Assurez-vous que le token a les permissions `write:packages` et `read:packages`
3. Régénérez un nouveau token si nécessaire

### Problème : Le build Docker échoue

**Cause** : Erreur dans le Dockerfile ou dépendances manquantes

**Solution** :
1. Testez le build en local :
   ```bash
   # Backend
   docker build -f production/Dockerfile -t test-backend .

   # Frontend
   docker build -t test-frontend .
   ```
2. Consultez les logs d'erreur dans GitHub Actions
3. Corrigez les erreurs et poussez à nouveau

### Problème : Le webhook Dokploy ne fonctionne pas

**Cause** : URL webhook incorrecte ou Dokploy inaccessible

**Solution** :
1. Vérifiez l'URL du webhook dans Dokploy
2. Testez manuellement le webhook :
   ```bash
   curl -X POST "https://your-dokploy.com/api/webhook/xxx"
   ```
3. Vérifiez que Dokploy est accessible depuis GitHub (pas de firewall bloquant)
4. Consultez les logs du workflow pour voir la réponse HTTP

### Problème : Confusion entre les secrets DOKPLOY_WEBHOOK_URL

**Cause** : Utilisation du même secret pour backend et frontend

**Solution** :
1. Créez deux secrets distincts :
   - `DOKPLOY_WEBHOOK_URL_BACKEND`
   - `DOKPLOY_WEBHOOK_URL_FRONTEND`
2. Mettez à jour les workflows pour utiliser les bons secrets
3. Supprimez l'ancien secret `DOKPLOY_WEBHOOK_URL` si présent

### Problème : L'application ne démarre pas après le déploiement

**Cause** : Variables d'environnement manquantes ou configuration incorrecte

**Solution** :
1. Vérifiez les logs de l'application dans Dokploy :
   ```bash
   docker logs ressurex-backend
   docker logs ressurex-frontend
   ```
2. Vérifiez que toutes les variables d'environnement sont configurées
3. Vérifiez les permissions sur les volumes Docker
4. Redémarrez manuellement l'application dans Dokploy

---

## 📊 Monitoring et logs

### GitHub Container Registry

Pour voir vos images publiées :
1. Allez sur votre profil GitHub → **Packages**
2. Vous verrez les packages `ressurex-backend` et `ressurex-frontend`
3. Cliquez sur un package pour voir les versions et les tags

### Logs GitHub Actions

Les logs sont disponibles pendant 90 jours :
```
Repository → Actions → Workflow → Build → Step logs
```

### Logs Dokploy

Logs en temps réel dans Dokploy :
```bash
# Via l'interface web
Dokploy → Application → Logs tab

# Via Docker CLI (si accès SSH au serveur)
docker logs -f --tail 100 ressurex-backend
docker logs -f --tail 100 ressurex-frontend
```

---

## 🔒 Sécurité

### Bonnes pratiques

1. **Tokens** :
   - Ne partagez jamais vos tokens GitHub
   - Régénérez les tokens régulièrement
   - Utilisez des tokens avec les permissions minimales nécessaires

2. **Secrets** :
   - Ne commitez jamais de secrets dans Git
   - Utilisez les GitHub Secrets pour toutes les données sensibles
   - Utilisez les variables d'environnement Dokploy pour les configurations

3. **Images Docker** :
   - Scannez régulièrement vos images pour les vulnérabilités
   - Mettez à jour les dépendances régulièrement
   - Utilisez des images de base officielles et maintenues

4. **Webhooks** :
   - Gardez les URLs de webhook privées
   - Utilisez HTTPS uniquement
   - Utilisez des secrets séparés pour backend et frontend

---

## 📚 Ressources supplémentaires

- [Documentation GitHub Actions](https://docs.github.com/en/actions)
- [Documentation GitHub Container Registry](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry)
- [Documentation Docker](https://docs.docker.com/)
- [Documentation Dokploy](https://dokploy.com/docs)
- [Documentation Laravel Deployment](https://laravel.com/docs/deployment)
- [Documentation Angular Deployment](https://angular.io/guide/deployment)

---

## 🔄 Différences avec ECJUEMOA

| Aspect | ECJUEMOA | Ressurex |
|--------|----------|----------|
| Branche de déploiement | `reviewcode` | `backoffice` |
| Secret backend | `DOKPLOY_WEBHOOK_URL` | `DOKPLOY_WEBHOOK_URL_BACKEND` |
| Secret frontend | `DOKPLOY_WEBHOOK_URL` | `DOKPLOY_WEBHOOK_URL_FRONTEND` |
| Domaine backend | `api.ecjuemoa.com` | `api.ressurex.com` |
| Domaine frontend | `app.ecjuemoa.com` | `app.ressurex.com` |

---

## 👥 Support

Pour toute question ou problème :

1. Consultez d'abord cette documentation
2. Vérifiez les logs GitHub Actions et Dokploy
3. Contactez l'équipe DevOps : devops@ressurex.com

---

**Dernière mise à jour** : 8 janvier 2026
**Version du document** : 1.0.0
**Basé sur** : ECJUEMOA Platform CI/CD
