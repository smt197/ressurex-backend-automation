<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\User;
use App\Services\OrionPolicyService;

class LanguagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return OrionPolicyService::viewAny($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Language $language): bool
    {
        return OrionPolicyService::view($user, $language);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        return OrionPolicyService::create($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, Language $language): bool
    {
        return OrionPolicyService::update($user, $language);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, Language $language): bool
    {
        return OrionPolicyService::delete($user, $language);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user, Language $language): bool
    {
        return OrionPolicyService::restore($user, $language);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user, Language $language): bool
    {
        return OrionPolicyService::forceDelete($user, $language);
    }

    /**
     * Determine whether the user can run search.
     */
    public function search(?User $user): bool
    {
        return OrionPolicyService::search($user);
    }

    /**
     * Determine whether the user can run batch operations.
     */
    public function batch(?User $user): bool
    {
        return OrionPolicyService::batch($user);
    }
}
