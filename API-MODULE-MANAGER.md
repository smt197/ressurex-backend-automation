# API Module Manager - Documentation

## Base URL
```
http://localhost:8000/api/admin/modules
```

## Authentication
Toutes les routes nécessitent une authentification via Sanctum (Bearer Token).

## Endpoints

### 1. Liste de tous les modules
```http
GET /api/admin/modules
```

**Réponse:**
```json
{
  "message": "Module list retrieved successfully",
  "data": [
    {
      "id": 1,
      "slug": "products",
      "moduleName": "products",
      "displayName": "Products",
      "displayNameSingular": "Product",
      "resourceType": "products",
      "identifierField": "id",
      "identifierType": "number",
      "requiresAuth": true,
      "routePath": "products",
      "fields": [...],
      "fieldsCount": 3,
      "enabled": true,
      "devMode": false,
      "createdAt": "2025-01-01T00:00:00.000000Z",
      "updatedAt": "2025-01-01T00:00:00.000000Z"
    }
  ]
}
```

### 2. Détails d'un module
```http
GET /api/admin/modules/{slug}
```

**Exemple:**
```http
GET /api/admin/modules/products
```

**Réponse:**
```json
{
  "message": "Module details retrieved successfully",
  "data": {
    "id": 1,
    "slug": "products",
    "moduleName": "products",
    "displayName": "Products",
    "displayNameSingular": "Product",
    "resourceType": "products",
    "identifierField": "id",
    "identifierType": "number",
    "requiresAuth": true,
    "routePath": "products",
    "fields": [
      {
        "name": "name",
        "type": "string",
        "required": true
      },
      {
        "name": "price",
        "type": "number",
        "required": true
      },
      {
        "name": "description",
        "type": "string",
        "required": false
      }
    ],
    "fieldsCount": 3,
    "enabled": true,
    "devMode": false,
    "translations": null,
    "createdAt": "2025-01-01T00:00:00.000000Z",
    "updatedAt": "2025-01-01T00:00:00.000000Z"
  }
}
```

### 3. Générer un nouveau module
```http
POST /api/admin/modules/generate
```

**Body:**
```json
{
  "moduleName": "products",
  "displayName": "Products",
  "displayNameSingular": "Product",
  "resourceType": "products",
  "identifierField": "id",
  "identifierType": "number",
  "requiresAuth": true,
  "routePath": "products",
  "devMode": false,
  "fields": [
    {
      "name": "name",
      "type": "string",
      "required": true
    },
    {
      "name": "price",
      "type": "number",
      "required": true
    },
    {
      "name": "description",
      "type": "string",
      "required": false
    }
  ]
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Module generated successfully!",
  "data": {
    "id": 1,
    "slug": "products",
    "moduleName": "products",
    ...
  }
}
```

### 4. Mettre à jour un module
```http
PUT /api/admin/modules/{slug}
```

**Exemple:**
```http
PUT /api/admin/modules/products
```

**Body:**
```json
{
  "displayName": "My Products",
  "enabled": true,
  "fields": [
    {
      "name": "name",
      "type": "string",
      "required": true
    },
    {
      "name": "price",
      "type": "number",
      "required": true
    }
  ]
}
```

**Réponse:**
```json
{
  "message": "Module updated successfully",
  "data": {
    ...
  }
}
```

### 5. Activer/Désactiver un module
```http
PATCH /api/admin/modules/{slug}/toggle
```

**Exemple:**
```http
PATCH /api/admin/modules/products/toggle
```

**Body:**
```json
{
  "enabled": false
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Module disabled successfully!",
  "data": {
    ...
  }
}
```

### 6. Régénérer les fichiers d'un module
```http
POST /api/admin/modules/{slug}/regenerate
```

**Exemple:**
```http
POST /api/admin/modules/products/regenerate
```

**Réponse:**
```json
{
  "success": true,
  "message": "Module regenerated successfully!",
  "data": {
    ...
  }
}
```

### 7. Obtenir les fichiers d'un module
```http
GET /api/admin/modules/{slug}/files
```

**Exemple:**
```http
GET /api/admin/modules/products/files
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "products.config.ts": "import { ModuleConfig } ...",
    "products.component.ts": "import { Component } ...",
    "products.resolver.ts": "import { createGenericResolver } ...",
    ...
  }
}
```

### 8. Supprimer un module
```http
DELETE /api/admin/modules/{slug}
```

**Exemple:**
```http
DELETE /api/admin/modules/products
```

**Réponse:**
```json
{
  "message": "Module deleted successfully",
  "data": {
    ...
  }
}
```

## Types de champs disponibles

Les types de champs supportés sont :
- `string` : Chaîne de caractères
- `number` : Nombre
- `boolean` : Booléen (true/false)
- `Date` : Date

## Notes importantes

1. **Nom du module** : Doit être en minuscules, au pluriel, sans espaces (ex: "products", "users", "categories")
2. **Identifiant** : Peut être "id" (number) ou "slug" (string)
3. **Mode développement** : Si `devMode: true`, le module généré utilisera des données mock au lieu d'appeler l'API
4. **Génération de fichiers** : La génération crée automatiquement :
   - Component TypeScript
   - Config TypeScript
   - Resolver TypeScript
   - Routes TypeScript
   - Model TypeScript
   - Interface TypeScript
   - Dialogs (Create/Update, Delete)
   - Traductions (en.json, fr.json, pt.json)
   - Si devMode: Mock service et mock data JSON

## Configuration requise

Dans le fichier `.env` du backend, assurez-vous d'avoir :
```env
FRONTEND_PATH=C:/laragon/www/resurex-frontend-automation
```

Ou laissez vide pour utiliser la valeur par défaut qui est calculée automatiquement.

## Exemple complet de test

### 1. Générer un module "tasks"

```bash
curl -X POST http://localhost:8000/api/admin/modules/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "moduleName": "tasks",
    "displayName": "Tasks",
    "displayNameSingular": "Task",
    "resourceType": "tasks",
    "identifierField": "id",
    "identifierType": "number",
    "requiresAuth": true,
    "routePath": "tasks",
    "devMode": true,
    "fields": [
      {
        "name": "title",
        "type": "string",
        "required": true
      },
      {
        "name": "completed",
        "type": "boolean",
        "required": false
      },
      {
        "name": "priority",
        "type": "number",
        "required": false
      }
    ]
  }'
```

### 2. Lister tous les modules

```bash
curl -X GET http://localhost:8000/api/admin/modules \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Désactiver le module "tasks"

```bash
curl -X PATCH http://localhost:8000/api/admin/modules/tasks/toggle \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"enabled": false}'
```

### 4. Supprimer le module "tasks"

```bash
curl -X DELETE http://localhost:8000/api/admin/modules/tasks \
  -H "Authorization: Bearer YOUR_TOKEN"
```
