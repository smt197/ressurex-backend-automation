# Template de Workflow CI/CD pour Ressurex Frontend

Lorsque vous créerez le projet `ressurex-frontend`, utilisez ce template pour le fichier `.github/workflows/ci.yaml`.

## Fichier : `.github/workflows/ci.yaml`

```yaml
name: Build and Deploy Docker Image

on:
  push:
    branches: ["main"]

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
          file: ./Dockerfile
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
          WEBHOOK_URL: ${{ secrets.DOKPLOY_WEBHOOK_URL_FRONTEND }}
        run: |
          set +e  # Désactiver l'arrêt sur erreur
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

## Différences avec le workflow Backend

| Élément | Backend | Frontend |
|---------|---------|----------|
| **Dockerfile path** | `./production/Dockerfile` | `./Dockerfile` |
| **Secret webhook** | `DOKPLOY_WEBHOOK_URL_BACKEND` | `DOKPLOY_WEBHOOK_URL_FRONTEND` |
| **Build time** | ~5-10 min | ~15-20 min (Angular build) |

## Secrets GitHub à configurer

Dans le repository `ressurex-frontend`, ajoutez ces secrets :

1. **GH_TOKEN** : Le même token GitHub utilisé pour le backend
2. **DOKPLOY_WEBHOOK_URL_FRONTEND** : L'URL du webhook Dokploy pour l'application frontend

## Étapes pour créer le frontend

```bash
# 1. Cloner le frontend ECJUEMOA
git clone https://github.com/votre-org/ecjuemoa-project-frontend.git ressurex-frontend

# 2. Aller dans le dossier
cd ressurex-frontend

# 3. Supprimer l'ancien remote
git remote remove origin

# 4. Ajouter le nouveau remote
git remote add origin https://github.com/votre-org/ressurex-frontend.git

# 5. Créer la branche backoffice
git checkout -b backoffice

# 6. Mettre à jour le nom dans package.json
# Changez "name": "vex" en "name": "ressurex"

# 7. Créer le dossier .github/workflows
mkdir -p .github/workflows

# 8. Créer le fichier ci.yaml avec le contenu du template ci-dessus

# 9. Commiter et pousser
git add .
git commit -m "chore: setup Ressurex frontend CI/CD"
git push -u origin backoffice
```

## Configuration du Dockerfile Frontend

Le frontend doit avoir un `Dockerfile` à la racine qui ressemble à ceci :

```dockerfile
# Stage 1: Build the Angular application
FROM node:18-alpine AS builder

# Set working directory
WORKDIR /app

# Install build dependencies
RUN apk add --no-cache python3 make g++

# Copy package files first for better caching
COPY package*.json ./

# Install dependencies with clean install for reproducible builds
RUN npm ci --legacy-peer-deps --silent

# Copy source files
COPY . .

# Build the Angular application for production with increased memory
ENV NODE_OPTIONS="--max-old-space-size=8192"
RUN npm run build

# Clean npm cache and remove unnecessary files to reduce image size
RUN npm cache clean --force && \
    rm -rf /root/.npm /tmp/* /app/node_modules

# Stage 2: Serve with nginx
FROM nginx:alpine

# Install wget for healthcheck
RUN apk add --no-cache wget

# Remove default nginx config
RUN rm -f /etc/nginx/conf.d/default.conf

# Copy custom nginx configuration
COPY nginx/nginx.conf /etc/nginx/conf.d/default.conf

# Copy built application from builder stage
COPY --from=builder /app/dist/vex /usr/share/nginx/html

# Set proper permissions
RUN chown -R nginx:nginx /usr/share/nginx/html && \
    chmod -R 755 /usr/share/nginx/html

# Expose port 80
EXPOSE 80

# Add healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD wget --no-verbose --tries=1 --spider http://localhost/health || exit 1

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
```

## Checklist avant le premier déploiement

- [ ] Repository `ressurex-frontend` créé sur GitHub
- [ ] Secrets GitHub configurés (`GH_TOKEN`, `DOKPLOY_WEBHOOK_URL_FRONTEND`)
- [ ] Fichier `.github/workflows/ci.yaml` créé avec le bon contenu
- [ ] Dockerfile présent à la racine du projet
- [ ] Configuration nginx présente dans `nginx/nginx.conf`
- [ ] Application créée dans Dokploy avec le webhook configuré
- [ ] Branche `backoffice` créée et poussée
- [ ] Premier build GitHub Actions réussi
- [ ] Application accessible via le domaine configuré

## Test du déploiement

Après le premier push sur `backoffice` :

1. **Vérifier GitHub Actions** :
   - Allez sur le repository → Actions
   - Vérifiez que le workflow s'exécute sans erreur

2. **Vérifier l'image Docker** :
   - Allez sur GitHub → Packages
   - Vérifiez que l'image `ressurex-frontend` est publiée

3. **Vérifier Dokploy** :
   - Connectez-vous à Dokploy
   - Vérifiez que l'application `ressurex-frontend` est en cours d'exécution
   - Consultez les logs pour vérifier qu'il n'y a pas d'erreurs

4. **Tester l'application** :
   ```bash
   # Test de disponibilité
   curl -I https://app.ressurex.com

   # Devrait retourner un code 200 OK
   ```

## Notes importantes

- **Branche de déploiement** : `backoffice` (différent de ECJUEMOA qui utilise `reviewcode`)
- **Secrets séparés** : Utilisez `DOKPLOY_WEBHOOK_URL_FRONTEND` (pas `DOKPLOY_WEBHOOK_URL`)
- **Build time** : Le build Angular peut prendre 15-20 minutes, c'est normal
- **Mémoire** : Le build Angular nécessite beaucoup de mémoire (8GB configurés dans NODE_OPTIONS)

---

**Dernière mise à jour** : 8 janvier 2026
