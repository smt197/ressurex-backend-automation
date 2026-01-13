<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Http\Resources\DocumentCollection;
use App\Http\Resources\DocumentResource;
use App\Models\User;
use Illuminate\Http\Request;
use Orion\Http\Controllers\RelationController;

class UserDocumentController extends RelationController
{
    protected $model = User::class;

    protected $relation = 'documents';

    protected $request = DocumentRequest::class;

    protected $resource = DocumentResource::class;

    protected $collectionResource = DocumentCollection::class;

    public function includes(): array
    {
        return ['documents'];
    }

    /**
     * Ajoute l'user_id avant enregistrement
     */
    protected function beforeStore(Request $request, $parentKey, $document)
    {
        $document->user_id = $parentKey;
        $request->request->remove('files');
    }

    protected function afterStore(Request $request, $parentKey, $document)
    {
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $document->addMedia($file)->toMediaCollection('attachments');
            }
        }
    }

    protected function afterUpdate(Request $request, $parentKey, $document)
    {
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $document->addMedia($file)->toMediaCollection('attachments');
            }
        }
    }

    /**
     * Pour forcer le `slug` comme identifiant
     */
    public function keyName(): string
    {
        return 'slug';
    }

    public function searchableBy(): array
    {
        return ['name', 'description'];
    }

    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }
}
