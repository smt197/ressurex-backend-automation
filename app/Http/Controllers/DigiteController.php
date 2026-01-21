<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigiteRequest;
use App\Http\Resources\DigiteResource;
use App\Http\Resources\Collections\DigiteCollection;
use App\Models\Digite;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class DigiteController extends Controller
{
    use DisableAuthorization;

    protected $model = Digite::class;
    protected $resource = DigiteResource::class;
    protected $collectionResource = DigiteCollection::class;
    protected $request = DigiteRequest::class;

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
