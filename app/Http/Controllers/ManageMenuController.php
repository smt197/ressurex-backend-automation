<?php

namespace App\Http\Controllers;

use App\Http\Resources\Collections\MenuCollection;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\User;
use App\Notifications\MenuUpdatedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class ManageMenuController extends Controller
{
    protected $model = Menu::class;

    protected $resource = MenuResource::class;

    protected $collectionResource = MenuCollection::class;

    public function keyName(): string
    {
        return 'slug';
    }

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
        return ['name', 'route', 'description'];
    }

    public function filterableBy(): array
    {
        return ['id', 'name', 'roles', 'category_id'];
    }

    public function sortableBy(): array
    {
        return ['id', 'name', 'route'];
    }

    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        // Eager load category relationship
        $query->with('category');

        // Si un rôle est envoyé dans la requête, on filtre automatiquement
        if ($role = $request->get('role')) {
            $query->whereJsonContains('roles', $role);
        }

        // Si une catégorie est envoyée dans la requête, on filtre automatiquement
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        return $query;
    }

    public function getMenusForCurrentUser(): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['data' => []]);
        }

        $userRoles = $user->roles->pluck('name')->toArray();

        $menus = Menu::query()
            ->with('category') // Eager load category relationship
            ->where(function ($query) use ($userRoles) {
                foreach ($userRoles as $role) {
                    $query->orWhereJsonContains('roles', $role);
                }
            })->get();

        return response()->json([
            'data' => MenuResource::collection($menus),
            'meta' => [
                'total' => $menus->count(),
                'user_roles' => $userRoles,
            ],
        ]);
    }

    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        parent::performStore($request, $entity, $attributes);
        $this->broadcastMenuUpdate('created');
    }

    protected function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        parent::performUpdate($request, $entity, $attributes);
        $this->broadcastMenuUpdate('updated');
    }

    protected function performDestroy(Model $entity): void
    {
        parent::performDestroy($entity);
        $this->broadcastMenuUpdate('deleted');
    }

    private function broadcastMenuUpdate(string $action): void
    {
        $allMenus = Menu::with('category')->orderBy('id')->get();
        $currentUserId = auth()->id() ?? 0;
        $users = User::all();

        Notification::send($users, new MenuUpdatedNotification($allMenus, $action, $currentUserId));
    }
}
