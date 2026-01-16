<?php

namespace App\Http\Controllers;

use App\Http\Requests\PluralRequest;
use App\Http\Resources\PluralResource;
use App\Http\Resources\Collections\PluralCollection;
use App\Models\Plural;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class PluralController extends Controller
{
    use DisableAuthorization;

    protected $model = Plural::class;
    protected $resource = PluralResource::class;
    protected $collectionResource = PluralCollection::class;
    protected $request = PluralRequest::class;

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
