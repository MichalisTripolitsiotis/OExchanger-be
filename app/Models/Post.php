<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Post
 *
 * @property int $id
 * @property string $title
 * @property string $text
 * @property int $user_id
 * @property int $community_id
 * @property-read Carbon|null $deleted_at
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 *
 */
class Post extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'text',
        'user_id',
        'community_id'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
