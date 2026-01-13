<?php

namespace App\Services;

use Illuminate\Foundation\Auth\User as Authenticatable;

class OrionPolicyService
{
    /**
     * Determine if the user can view any resources.
     */
    public static function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine if the user can view the resource.
     */
    public static function view(?Authenticatable $user, $resource): bool
    {
        return $user !== null;
    }

    /**
     * Determine if the user can create resources.
     */
    public static function create(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine if the user can update the resource.
     */
    public static function update(?Authenticatable $user, $resource): bool
    {
        return $user !== null;
    }

    /**
     * Determine if the user can delete the resource.
     */
    public static function delete(?Authenticatable $user, $resource): bool
    {
        return $user !== null;
    }

    /**
     * Determine if the user can restore the resource.
     */
    public static function restore(?Authenticatable $user, $resource): bool
    {
        return $user !== null;
    }

    /**
     * Determine if the user can permanently delete the resource.
     */
    public static function forceDelete(?Authenticatable $user, $resource): bool
    {
        return $user !== null;
    }

    /**
     * Determine if the user can run search.
     */
    public static function search(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine if the user can run batch operations.
     */
    public static function batch(?Authenticatable $user): bool
    {
        return $user !== null;
    }
}
