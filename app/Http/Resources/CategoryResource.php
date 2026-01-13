<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $displayName = 'categories.'.strtolower($this->name);
        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'display_name' => __($displayName),
            'order' => $this->order,
            'icon' => $this->icon,
            'navigation_type' => $this->navigation_type,
            'position_reference_id' => $this->position_reference_id,
            'position_type' => $this->position_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Si c'est une requête GET et que l'URL contient un ID (show)
        if ($request->isMethod('get') && $request->route('category')) {
            return [
                'message' => __('categories.show'),
                'data' => $data,
            ];
        }
        // Si c'est POST, PATCH ou DELETE
        elseif ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('delete')) {
            if (strpos($request->getPathInfo(), 'search') === false) {
                return [
                    'message' => $this->successMessage($request),
                    'data' => $data,
                ];
            }
        }

        return $data;
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'POST':
                return __('categories.created');
            case 'PATCH':
                return __('categories.updated');
            case 'PUT':
                return __('categories.updated');
            case 'GET':
                return __('categories.list');
            case 'DELETE':
                return __('categories.deleted');
            default:
                return __('categories.list');
        }
    }
}
