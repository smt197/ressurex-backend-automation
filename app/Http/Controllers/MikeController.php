<?php

namespace App\Http\Controllers;

use App\Http\Requests\MikeRequest;
use App\Http\Resources\MikeResource;
use App\Http\Resources\Collections\MikeCollection;
use App\Models\Mike;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class MikeController extends Controller
{
    use DisableAuthorization;

    protected $model = Mike::class;
    protected $resource = MikeResource::class;
    protected $collectionResource = MikeCollection::class;
    protected $request = MikeRequest::class;

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
