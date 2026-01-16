<?php

namespace App\Http\Controllers;

use App\Http\Requests\LikeRequest;
use App\Http\Resources\LikeResource;
use App\Http\Resources\Collections\LikeCollection;
use App\Models\Like;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class LikeController extends Controller
{
    use DisableAuthorization;

    protected $model = Like::class;
    protected $resource = LikeResource::class;
    protected $collectionResource = LikeCollection::class;
    protected $request = LikeRequest::class;

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
