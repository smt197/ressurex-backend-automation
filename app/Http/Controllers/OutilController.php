<?php

namespace App\Http\Controllers;

use App\Http\Requests\OutilRequest;
use App\Http\Resources\OutilResource;
use App\Http\Resources\Collections\OutilCollection;
use App\Models\Outil;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class OutilController extends Controller
{
    use DisableAuthorization;

    protected $model = Outil::class;
    protected $resource = OutilResource::class;
    protected $collectionResource = OutilCollection::class;
    protected $request = OutilRequest::class;

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
