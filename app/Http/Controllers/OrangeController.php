<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrangeRequest;
use App\Http\Resources\OrangeResource;
use App\Http\Resources\Collections\OrangeCollection;
use App\Models\Orange;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class OrangeController extends Controller
{
    use DisableAuthorization;

    protected $model = Orange::class;
    protected $resource = OrangeResource::class;
    protected $collectionResource = OrangeCollection::class;
    protected $request = OrangeRequest::class;

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
        return ['name', 'desc'];
    }

    public function sortableBy(): array
    {
        return ['name', 'desc', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['name', 'desc'];
    }
}
