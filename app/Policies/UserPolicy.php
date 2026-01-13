<?php

namespace App\Policies;

use App\Models\User;
use App\Services\OrionPolicyService;

class UserPolicy
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
    public function view(?User $user, User $model): bool
    {
        return OrionPolicyService::view($user, $model);
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
    public function update(?User $user, User $model): bool
    {
        return OrionPolicyService::update($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, User $model): bool
    {
        return OrionPolicyService::delete($user, $model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user, User $model): bool
    {
        return OrionPolicyService::restore($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user, User $model): bool
    {
        return OrionPolicyService::forceDelete($user, $model);
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
