<?php

namespace App\Http\Controllers;

use App\Http\Requests\LifeRequest;
use App\Http\Resources\LifeResource;
use App\Http\Resources\Collections\LifeCollection;
use App\Models\Life;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class LifeController extends Controller
{
    use DisableAuthorization;

    protected $model = Life::class;
    protected $resource = LifeResource::class;
    protected $collectionResource = LifeCollection::class;
    protected $request = LifeRequest::class;

    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }

    public function searchableBy(): array
    {
        return ['name', 'description'];
    }

    public function sortableBy(): array
    {
        return ['name', 'description', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['name', 'description'];
    }
}
