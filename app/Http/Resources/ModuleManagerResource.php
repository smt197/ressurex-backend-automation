<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModuleManagerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'moduleName' => $this->module_name,
            'displayName' => $this->display_name,
            'displayNameSingular' => $this->display_name_singular,
            'resourceType' => $this->resource_type,
            'identifierField' => $this->identifier_field,
            'identifierType' => $this->identifier_type,
            'requiresAuth' => $this->requires_auth,
            'routePath' => $this->route_path,
            'fields' => $this->fields,
            'fieldsCount' => count($this->fields ?? []),
            'enabled' => $this->enabled,
            'devMode' => $this->dev_mode,
            'roles' => $this->roles ?? ['user'],
            'actions' => $this->actions,
            'translations' => $this->translations,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        // Si c'est une requête GET et que l'URL contient un ID (show)
        if ($request->isMethod('get') && $request->route('module_manager')) {
            return [
                'message' => __('Module details retrieved successfully'),
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
                return __('modules.created');
            case 'PATCH':
                return __('modules.updated');
            case 'PUT':
                return __('modules.updated');
            case 'GET':
                return __('modules.list');
            case 'DELETE':
                return __('modules.deleted');
            default:
                return __('Module list retrieved successfully');
        }
    }
}
