<?php

namespace App\Http\Controllers;

use App\Http\Requests\GreffeRequest;
use App\Http\Resources\GreffeResource;
use App\Http\Resources\Collections\GreffeCollection;
use App\Models\Greffe;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class GreffeController extends Controller
{
    use DisableAuthorization;

    protected $model = Greffe::class;
    protected $resource = GreffeResource::class;
    protected $collectionResource = GreffeCollection::class;
    protected $request = GreffeRequest::class;

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
        return ['name', 'email'];
    }

    public function sortableBy(): array
    {
        return ['name', 'email', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['name', 'email'];
    }
}
