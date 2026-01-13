<?php

namespace App\Http\Controllers;

use App\Http\Requests\LanguageRequest;
use App\Http\Resources\Collections\LanguageCollection;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Orion\Http\Controllers\Controller;

class LanguageController extends Controller
{
    /**
     * Modèle Eloquent associé à ce contrôleur
     *
     * @var string
     */
    protected $model = Language::class;

    /**
     * Resource pour transformer un seul élément de langue
     *
     * @var string
     */
    protected $resource = LanguageResource::class;

    /**
     * Resource pour transformer une collection de langues
     *
     * @var string
     */
    protected $collectionResource = LanguageCollection::class;

    /**
     * Classe de requête pour la validation
     *
     * @var string
     */
    protected $request = LanguageRequest::class;

    /**
     * Limite de pagination par défaut
     */
    public function limit(): int
    {
        return config('app.limit_pagination', 15);
    }

    /**
     * Limite maximale de pagination
     */
    public function maxLimit(): int
    {
        return config('app.max_pagination', 100);
    }

    /**
     * Attributs utilisés pour la recherche
     */
    public function searchableBy(): array
    {
        return ['code', 'name'];
    }

    /**
     * Champs pouvant être inclus via le paramètre ?include=
     */
    public function includes(): array
    {
        return ['user'];
    }

    /**
     * Champs pouvant être utilisés pour le tri
     */
    public function sortableBy(): array
    {
        return ['id', 'code', 'name', 'created_at', 'updated_at'];
    }

    /**
     * Filtres personnalisés pour les requêtes
     */
    public function filterableBy(): array
    {
        return ['id', 'code', 'name'];
    }
}
