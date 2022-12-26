<?php

namespace App\Models;

use App\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property-read Carbon|null $email_verified_at
 * @property string $password
 * @property string $remember_token
 * @property-read Carbon|null $deleted_at
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 *
 * @property-write string $url
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @return BelongsToMany
     */
    public function moderatedCommunities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'moderated_communities')->withTimestamps();
    }

    /**
     * @return BelongsToMany
     */
    public function subscribedCommunities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'subscribed_communities')->withTimestamps();
    }

    /**
     * Generate a secure password.
     *
     * @param int $bytes
     * @return string
     */
    public static function generatePassword($bytes = 16): string
    {
        $randBin = openssl_random_pseudo_bytes($bytes);
        return bin2hex($randBin);
    }

    /**
     * @override
     *
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail());
    }
}
