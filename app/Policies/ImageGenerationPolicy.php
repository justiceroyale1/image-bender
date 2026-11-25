<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImageGeneration;
use App\Models\User;

class ImageGenerationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImageGeneration $imageGeneration): bool
    {
        return $imageGeneration->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImageGeneration $imageGeneration): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImageGeneration $imageGeneration): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ImageGeneration $imageGeneration): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ImageGeneration $imageGeneration): bool
    {
        return false;
    }
}
