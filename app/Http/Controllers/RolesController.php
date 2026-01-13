<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolesRequest;
use App\Http\Resources\Collections\RolesCollection;
use App\Http\Resources\RolesResource;
use App\Models\Roles;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class RolesController extends Controller
{
    use DisableAuthorization;

    /**
     * The Eloquent model associated with this controller.
     *
     * @var string
     */
    protected $model = Roles::class;

    /**
     * The resource associated with the model.
     *
     * @var string
     */
    protected $resource = RolesResource::class;

    /**
     * The collection resource associated with the model.
     *
     * @var string
     */
    protected $collectionResource = RolesCollection::class;

    /**
     * The request class for validation.
     *
     * @var string
     */
    protected $request = RolesRequest::class;

    /**
     * Default pagination limit.
     */
    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    /**
     * Maximum pagination limit.
     */
    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }

    /**
     * The attributes that are used for searching.
     */
    public function searchableBy(): array
    {
        return ['name'];
    }

    public function sortableBy(): array
    {
        // Autorisez le tri sur les colonnes visibles dans votre frontend.
        // Assurez-vous que ces noms correspondent exactement aux colonnes de votre BDD.
        return ['display_name', 'guard_name', 'created_at'];
    }

    public function filterableBy(): array
    {
        // Utile si vous voulez filtrer par garde un jour. Par exemple: ?filter[guard_name]=web
        return ['guard_name'];
    }
}
