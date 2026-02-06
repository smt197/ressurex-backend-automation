<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OutilResource extends JsonResource
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

        if ($request->isMethod('get') && $request->route('outil')) {
            return [
                'message' => __('outils.show'),
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
                return __('outils.created');
            case 'PATCH':
            case 'PUT':
                return __('outils.updated');
            case 'GET':
                return __('outils.list');
            case 'DELETE':
                return __('outils.deleted');
            default:
                return __('outils.list');
        }
    }
}
