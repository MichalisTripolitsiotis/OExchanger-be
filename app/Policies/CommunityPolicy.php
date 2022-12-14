<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Community $community)
    {
        if ($user->moderatedCommunities->contains($community) || $user->subscribedCommunities->contains($community)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Community $community)
    {
        return $user->moderatedCommunities->contains($community);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Community $community)
    {
        return $user->moderatedCommunities->contains($community);
    }
}
