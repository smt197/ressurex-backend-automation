<?php

namespace App\Policies;

use App\Models\Country;
use App\Models\User;
use App\Services\OrionPolicyService;

class CountryPolicy
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
    public function view(?User $user, Country $country): bool
    {
        return OrionPolicyService::view($user, $country);
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
    public function update(?User $user, Country $country): bool
    {
        return OrionPolicyService::update($user, $country);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, Country $country): bool
    {
        return OrionPolicyService::delete($user, $country);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user, Country $country): bool
    {
        return OrionPolicyService::restore($user, $country);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user, Country $country): bool
    {
        return OrionPolicyService::forceDelete($user, $country);
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
