<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GreffeResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($request->isMethod('get') && $request->route('greffe')) {
            return [
                'message' => __('greffes.show'),
                'data' => $data,
            ];
        } elseif ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('delete')) {
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
                return __('greffes.created');
            case 'PATCH':
            case 'PUT':
                return __('greffes.updated');
            case 'GET':
                return __('greffes.list');
            case 'DELETE':
                return __('greffes.deleted');
            default:
                return __('greffes.list');
        }
    }
}
