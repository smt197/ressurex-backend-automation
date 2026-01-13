<?php

namespace App\Http\Controllers;

use App\Http\Resources\LanguageResource;
use App\Models\User;
use Illuminate\Http\Request;
use Orion\Http\Controllers\RelationController;

class UserLanguageController extends RelationController
{
    protected $model = User::class;

    protected $relation = 'languages';

    protected $resource = LanguageResource::class;

    public function include(): array
    {
        return ['pivot'];
    }

    public function associate(Request $request, ...$args)
    {
        // Validate required parameters
        $request->validate([
            'related_key' => 'required|integer|exists:languages,id',
        ]);

        /** @var User $user */
        $user = User::findOrFail($args[0]);
        $newLanguageId = $request->input('related_key');

        // Check for existing association
        if ($user->languages()->where('languages.id', $newLanguageId)->exists()) {
            // $userlanguage = new LanguageResource($user->languages()->find($newLanguageId));
            $userlanguage = new LanguageResource($user->languages()->first());

            // Return without changes if already associated
            return response()->json([
                'message' => __('language.already_associated'),
                'language' => $userlanguage->code,
            ], 200);
        }

        // Dissociate all existing languages
        $user->languages()->detach();

        // Associate the new language
        $user->languages()->attach($newLanguageId, [
            'is_preferred' => true, // Set the new language as preferred
        ]);

        // Return the newly associated language
        return response()->json([
            'message' => __('language.message'),
        ], 200);
    }
}
