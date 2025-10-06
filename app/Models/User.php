<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'gender',
        'bdate',
        'address',
        'username',
        'email',
        'password',
        'photo',
        'role',
        'plan',
        'token_balance',
        'skill_id',
        'is_verified',
        'email_verified_at',
        'google_verified',
        'firebase_uid',
        'firebase_provider',
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
        'bdate' => 'date',
    ];

    /**
     * Get the user's primary skill.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function skill()
    {
        return $this->belongsTo(\App\Models\Skill::class, 'skill_id', 'skill_id');
    }

    /**
     * Get all skills associated with the user.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\Skill[]
     */
    public function skills()
    {
        return $this->belongsToMany(\App\Models\Skill::class, 'user_skills', 'user_id', 'skill_id', 'id', 'skill_id');
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute()
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    /**
     * Find user by username or email for authentication.
     */
    public function findForPassport($username)
    {
        return $this->where('username', $username)->orWhere('email', $username)->first();
    }

    /**
     * Find user by Firebase UID.
     */
    public function findByFirebaseUid($uid)
    {
        return $this->where('firebase_uid', $uid)->first();
    }

    /**
     * Create or update user from Firebase auth data.
     */
    public function createOrUpdateFromFirebase($firebaseUser, $provider = 'email')
    {
        $userData = [
            'firebase_uid' => $firebaseUser['uid'],
            'firebase_provider' => $provider,
            'email' => $firebaseUser['email'] ?? null,
            'is_verified' => $firebaseUser['email_verified'] ?? false,
        ];

        // Extract name parts if available
        if (isset($firebaseUser['display_name']) && $firebaseUser['display_name']) {
            $nameParts = explode(' ', $firebaseUser['display_name'], 2);
            $userData['firstname'] = $nameParts[0] ?? '';
            $userData['lastname'] = $nameParts[1] ?? '';
        }

        // Generate username if not provided
        if (!isset($userData['username']) && isset($userData['email'])) {
            $userData['username'] = explode('@', $userData['email'])[0];
        }

        // Set default values
        $userData['role'] = $userData['role'] ?? 'user';
        $userData['plan'] = $userData['plan'] ?? 'free';
        $userData['token_balance'] = $userData['token_balance'] ?? 0;

        return $this->updateOrCreate(
            ['firebase_uid' => $firebaseUser['uid']],
            $userData
        );
    }
}
