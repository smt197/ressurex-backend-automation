# Configuration GitHub API

## Génération du Token GitHub

Pour synchroniser vos repositories GitHub avec l'application, vous devez générer un Personal Access Token (PAT) depuis GitHub.

### Étapes pour créer un token:

1. **Aller sur GitHub Settings**
   - Connectez-vous à GitHub
   - Allez dans `Settings` > `Developer settings` > `Personal access tokens` > `Tokens (classic)`
   - Ou accédez directement à: https://github.com/settings/tokens

2. **Générer un nouveau token**
   - Cliquez sur `Generate new token` > `Generate new token (classic)`
   - Donnez un nom à votre token (ex: "Resurex Backend")
   - Sélectionnez une expiration (recommandé: 90 jours ou plus)

3. **Sélectionner les permissions (scopes)**

   Pour que la synchronisation fonctionne correctement, sélectionnez au minimum:

   - ✅ `repo` (accès complet aux repositories privés et publics)
     - repo:status
     - repo_deployment
     - public_repo
     - repo:invite
     - security_events

   Permissions optionnelles mais recommandées:
   - ✅ `read:user` (lire les informations du profil utilisateur)
   - ✅ `user:email` (accès à l'email)

4. **Générer et copier le token**
   - Cliquez sur `Generate token`
   - ⚠️ **IMPORTANT**: Copiez immédiatement le token, vous ne pourrez plus le voir après!

## Configuration dans l'application

### 1. Ajouter le token dans le fichier .env

Ouvrez le fichier `.env` à la racine du backend et ajoutez:

```env
GITHUB_TOKEN=ghp_votre_token_ici
```

Remplacez `ghp_votre_token_ici` par le token que vous avez copié.

### 2. Nettoyer le cache (optionnel)

```bash
php artisan config:clear
php artisan cache:clear
```

## Test de la connexion

Vous pouvez tester la connexion via Tinker:

```bash
php artisan tinker
```

Puis exécutez:

```php
$github = new \App\Services\GithubApiService();
echo $github->hasToken() ? '✓ Token configured' : '✗ Token missing';
echo PHP_EOL;
echo $github->testConnection() ? '✓ Connection successful' : '✗ Connection failed';
```

## Utilisation

### Via l'interface web

1. Connectez-vous à l'application
2. Allez dans le menu "GitHub"
3. Cliquez sur le bouton "Sync from GitHub"
4. Vos repositories seront synchronisés automatiquement

### Via l'API

```bash
curl -X POST http://localhost:8000/api/github-repositories/sync-from-github \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json"
```

## Fonctionnalités de synchronisation

La synchronisation récupère automatiquement:

- ✅ Tous vos repositories (publics et privés)
- ✅ Les informations du repository (nom, description, URL, etc.)
- ✅ Toutes les branches de chaque repository
- ✅ L'état de protection des branches
- ✅ Les informations du dernier commit (SHA, message, date)

## Résolution des problèmes

### Erreur: "GitHub token not configured"
- Vérifiez que `GITHUB_TOKEN` est bien dans votre fichier `.env`
- Redémarrez votre serveur Laravel après avoir modifié `.env`

### Erreur: "Unable to connect to GitHub API"
- Vérifiez que le token est valide et n'a pas expiré
- Vérifiez que le token a les bonnes permissions (scopes)
- Vérifiez votre connexion internet

### Erreur: 403 ou 401
- Le token est invalide ou expiré
- Les permissions (scopes) sont insuffisantes
- Régénérez un nouveau token avec les bonnes permissions

### Rate Limiting
- GitHub API limite à 5000 requêtes/heure pour les requêtes authentifiées
- Si vous avez beaucoup de repositories, la synchronisation peut prendre du temps
- Les logs Laravel contiennent les détails de chaque erreur

## Sécurité

⚠️ **IMPORTANT**:
- Ne commitez JAMAIS votre token dans Git
- Ajoutez `.env` dans `.gitignore` (déjà fait par défaut)
- Régénérez le token immédiatement si vous pensez qu'il a été compromis
- Utilisez des tokens avec les permissions minimales nécessaires
- Configurez une date d'expiration raisonnable (90 jours recommandés)

## Logs

Les logs de synchronisation se trouvent dans `storage/logs/laravel.log`:

```bash
tail -f storage/logs/laravel.log | grep GitHub
```

## Plus d'informations

- Documentation GitHub API: https://docs.github.com/en/rest
- Documentation Personal Access Tokens: https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token
