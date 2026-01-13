<?php

namespace App\Http\Resources;

use App\Services\NotificationService;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $locale = app()->getLocale();

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->getDescriptionForLocale($locale),
            'descriptions' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'route' => $this->route,
            'roles' => $this->roles,
            'slug' => $this->slug,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category'),
            'disable' => $this->disable,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        // Si c'est une requête GET et que l'URL contient un ID (show)
        if ($request->isMethod('get') && $request->route('user')) {

            return [
                'message' => __('user.show'),
                'data' => $data,
            ];
        }
        // Si c'est POST, PATCH ou DELETE
        elseif ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('delete')) {
            if (strpos($request->getPathInfo(), 'search') === false) {
                $messageContent = $this->successMessage($request);
                NotificationService::sendNewNotification($messageContent);
                activity()->event($request->method())->causedBy($this->clone)->log($messageContent);

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
                return __('menus.created');
            case 'PATCH':
                return __('menus.updated');
            case 'PUT':
                return __('menus.updated');
            case 'GET':
                return __('menus.list');
            case 'DELETE':
                return __('menus.deleted');
            default:
                return __('menus.list');
        }
    }
}
