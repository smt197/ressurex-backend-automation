<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\Collections\OrderCollection;
use App\Models\Order;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class OrderController extends Controller
{
    use DisableAuthorization;

    protected $model = Order::class;
    protected $resource = OrderResource::class;
    protected $collectionResource = OrderCollection::class;
    protected $request = OrderRequest::class;

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
