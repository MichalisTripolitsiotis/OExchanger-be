<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\UserCommunities
 *
 * @property int $id
 * @property int $user_id
 * @property int $community_id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 *
 */
class UserCommunities extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
}
