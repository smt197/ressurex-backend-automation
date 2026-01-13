<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\Collections\CategoryCollection;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class CategoryController extends Controller
{
    /**
     * The Eloquent model associated with this controller.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * The resource associated with the model.
     *
     * @var string
     */
    protected $resource = CategoryResource::class;

    /**
     * The collection resource associated with the model.
     *
     * @var string
     */
    protected $collectionResource = CategoryCollection::class;

    /**
     * The request class for validation.
     *
     * @var string
     */
    protected $request = CategoryRequest::class;

    public function keyName(): string
    {
        return 'slug';
    }

    /**
     * Default pagination limit.
     */
    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    /**
     * Maximum pagination limit.
     */
    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }

    /**
     * The attributes that are used for searching.
     */
    public function searchableBy(): array
    {
        return ['name'];
    }

    public function sortableBy(): array
    {
        return ['name', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['name'];
    }

    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        if ($request->has('position_reference_id') && $request->has('position_type')) {
            $entity->order = Category::calculateNewOrder(
                $request->input('position_reference_id'),
                $request->input('position_type')
            );
        } elseif (! $entity->order) {
            $entity->order = (Category::max('order') ?? 0) + 1;
        }

        parent::performStore($request, $entity, $attributes);
    }

    protected function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        if ($request->has('position_reference_id') && $request->has('position_type')) {
            $entity->order = Category::calculateNewOrder(
                $request->input('position_reference_id'),
                $request->input('position_type')
            );
        }

        parent::performUpdate($request, $entity, $attributes);
    }
}
