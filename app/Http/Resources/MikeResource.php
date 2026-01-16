<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MikeResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($request->isMethod('get') && $request->route('mike')) {
            return [
                'message' => __('mikes.show'),
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
                return __('mikes.created');
            case 'PATCH':
            case 'PUT':
                return __('mikes.updated');
            case 'GET':
                return __('mikes.list');
            case 'DELETE':
                return __('mikes.deleted');
            default:
                return __('mikes.list');
        }
    }
}
