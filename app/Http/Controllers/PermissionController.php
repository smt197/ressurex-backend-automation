<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionsRequest;
use App\Http\Resources\Collections\PermissionsCollection;
use App\Http\Resources\PermissionsResource;
use App\Models\Permissions;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class PermissionController extends Controller
{
    use DisableAuthorization;

    /**
     * The Eloquent model associated with this controller.
     *
     * @var string
     */
    protected $model = Permissions::class;

    /**
     * The resource associated with the model.
     *
     * @var string
     */
    protected $resource = PermissionsResource::class;

    /**
     * The collection resource associated with the model.
     *
     * @var string
     */
    protected $collectionResource = PermissionsCollection::class;

    /**
     * The request class for validation.
     *
     * @var string
     */
    protected $request = PermissionsRequest::class;

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
}
