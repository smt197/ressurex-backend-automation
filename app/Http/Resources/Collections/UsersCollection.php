<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\UsersResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class UsersCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {

        $data = [
            'message' => $this->successMessage($request),
            'data' => UsersResource::collection($this->collection),
            'roles' => $this->getRoles(),
            'permissions' => $this->getAvailablePermissions(),
            'pagination' => $this->when(
                $this->resource instanceof \Illuminate\Pagination\AbstractPaginator,
                function () {
                    return [
                        'total' => $this->resource->total(),
                        'current_page' => $this->resource->currentPage(),
                        'per_page' => $this->resource->perPage(),
                    ];
                }
            ),

        ];

        return $data;
    }

    public function withResponse($request, $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);

        // Remove default Laravel pagination keys if they exist
        unset(
            $jsonResponse['links'],
            $jsonResponse['meta'],
            $jsonResponse['data']['links'],
            $jsonResponse['data']['meta']
        );

        $response->setContent(json_encode($jsonResponse));
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'GET':
                return __('user.list');
            case 'DELETE':
                return __('user.deleted');
            default:
                return '';
        }
    }

    public function getRoles()
    {
        $roles = DB::table('roles')->get();

        $originalResponse = new RolesCollection($roles);

        // Extraire les données de la collection et les restructurer
        $responseArray = $originalResponse->toArray(request());

        return $responseArray['data'];
    }

    public function getAvailablePermissions()
    {
        $permissions = DB::table('permissions')->get();
        $originalResponse = new PermissionsCollection($permissions);
        // Extraire les données de la collection et les restructurer
        $responseArray = $originalResponse->toArray(request());

        return $responseArray['data'];
    }
}
