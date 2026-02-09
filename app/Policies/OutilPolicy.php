<?php

namespace App\Policies;

use App\Models\Outil;
use App\Models\User;
use App\Services\OrionPolicyService;

class OutilPolicy
{
    public function viewAny(?User $user): bool
    {
        return OrionPolicyService::viewAny($user);
    }

    public function view(?User $user, Outil $outil): bool
    {
        return OrionPolicyService::view($user, $outil);
    }

    public function create(?User $user): bool
    {
        return OrionPolicyService::create($user);
    }

    public function update(?User $user, Outil $outil): bool
    {
        return OrionPolicyService::update($user, $outil);
    }

    public function delete(?User $user, Outil $outil): bool
    {
        return OrionPolicyService::delete($user, $outil);
    }

    public function restore(?User $user, Outil $outil): bool
    {
        return OrionPolicyService::restore($user, $outil);
    }

    public function forceDelete(?User $user, Outil $outil): bool
    {
        return OrionPolicyService::forceDelete($user, $outil);
    }

    public function search(?User $user): bool
    {
        return OrionPolicyService::search($user);
    }

    public function batch(?User $user): bool
    {
        return OrionPolicyService::batch($user);
    }
}
