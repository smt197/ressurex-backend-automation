<?php

return [

    /*
     * Language lines will be fetched by these loaders. You can put any class here that implements
     * the Spatie\TranslationLoader\TranslationLoaders\TranslationLoader-interface.
     */
    'translation_loaders' => [
        Spatie\TranslationLoader\TranslationLoaders\Db::class,
    ],
    'replacements' => [
        'en' => [':'], // Format des variables pour l'anglais
        'fr' => [':'], // Format des variables pour le français
        'pt' => [':'], // Format des variables pour le portuguais
    ],

    /*
     * This is the model used by the Db Translation loader. You can put any model here
     * that extends Spatie\TranslationLoader\LanguageLine.
     */
    'model' => Spatie\TranslationLoader\LanguageLine::class,

    /*
     * This is the translation manager which overrides the default Laravel `translation.loader`
     */
    'translation_manager' => Spatie\TranslationLoader\TranslationLoaderManager::class,

    'cache' => [
        'enabled' => env('TRANSLATION_CACHE_ENABLED', true),
        'timeout' => 60,
    ],

];
