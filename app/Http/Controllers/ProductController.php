<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\Collections\ProductCollection;
use App\Models\Product;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class ProductController extends Controller
{
    use DisableAuthorization;

    protected $model = Product::class;
    protected $resource = ProductResource::class;
    protected $collectionResource = ProductCollection::class;
    protected $request = ProductRequest::class;

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
        return ['name', 'price'];
    }

    public function sortableBy(): array
    {
        return ['name', 'price', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['name', 'price'];
    }
}
