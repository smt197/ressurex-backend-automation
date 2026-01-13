<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug; // AJOUTER CECI
use Spatie\Sluggable\SlugOptions; // AJOUTER CECI

class Document extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'name',
        'allias_name',
        'size',
        'description',
        'user_id',
        'slug',
    ];

    /**
     * Définir la collection de médias.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['image/png', 'image/jpg', 'image/jpeg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->singleFile()
            ->useDisk('documents');
    }


    /**
     * Relation: un document appartient à un utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSlugOptions(): SlugOptions
    {

        return SlugOptions::create()
            ->generateSlugsFrom(['name', 'description']) // Ou par exemple ->generateSlugsFrom('email') ou un champ 'username'
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate(); // Optionnel: si vous ne voulez pas que le slug change si le nom/prénom change
    }

    public function getInfoMedia($document)
    {
        $mediaItems = $document->getMedia('attachments');

        return $mediaItems->map(function ($mediaItem) {
            $docId = $mediaItem->getCustomProperty('document_id');
            $document = Document::find($docId);

            return [
                'id' => $mediaItem->id, // Utile pour pouvoir supprimer un fichier spécifique
                'name' => $mediaItem->name,
                'file_name' => $mediaItem->file_name,
                'size' => $mediaItem->size,
                'mime_type' => $mediaItem->mime_type,
                'url' => route('documents.serve', [
                    'document' => $document->slug,
                    'filename' => $mediaItem->file_name,
                ]),
                'slug' => $document->slug,
            ];
        });
    }
}
