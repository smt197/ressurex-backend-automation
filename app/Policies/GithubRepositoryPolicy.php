<?php

namespace App\Policies;

use App\Models\GithubRepository;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GithubRepositoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir la liste
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GithubRepository $githubRepository): bool
    {
        // L'utilisateur peut voir seulement ses propres repositories
        return $user->id === $githubRepository->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent créer des repositories
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GithubRepository $githubRepository): bool
    {
        // L'utilisateur peut mettre à jour seulement ses propres repositories
        return $user->id === $githubRepository->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GithubRepository $githubRepository): bool
    {
        // L'utilisateur peut supprimer seulement ses propres repositories
        return $user->id === $githubRepository->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GithubRepository $githubRepository): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GithubRepository $githubRepository): bool
    {
        return false;
    }
}
