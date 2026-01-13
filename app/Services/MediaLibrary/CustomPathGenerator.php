<?php

namespace App\Services\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    /**
     * Collection mappings with their parent directories.
     * Based on DIRECTORIES from App\Console\Commands\SetupStorageDirectories
     *
     * @var array<string, string>
     */
    private const COLLECTION_MAPPINGS = [
        'profils' => 'profils',
        'temporaryDirectory' => 'temporaryDirectory',
        'temporaryProfils' => 'temporaryDirectory/profils',
        'apps' => 'apps',
        'logos' => 'apps/logos',
        'appLogos' => 'apps/logos',
        'chats' => 'chats',
        'documents' => 'documents',
        'attachments' => 'documents',
        'chat_attachments' => 'chat_attachments',
    ];

    /**
     * Get the path for the given media item.
     *
     * @param  Media  $media  The media item
     * @return string The path where files should be stored
     */
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/'.$media->id.'/';
    }

    /**
     * Get the path for conversions of the given media item.
     *
     * @param  Media  $media  The media item
     * @return string The path where conversions should be stored
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    /**
     * Get the path for responsive images of the given media item.
     *
     * @param  Media  $media  The media item
     * @return string The path where responsive images should be stored
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }

    /**
     * Get the base path for a media item based on its collection name.
     *
     * @param  Media  $media  The media item
     * @return string The base path for the media collection
     */
    private function getBasePath(Media $media): string
    {
        return self::COLLECTION_MAPPINGS[$media->collection_name]
            ?? $media->collection_name;
    }
}
