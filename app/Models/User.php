<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPassword;

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
        'bdate_edited',
        'address',
        'username',
        'username_edited',
        'email',
        'email_edited',
        'password',
        'photo',
        'role',
        'plan',
        'token_balance',
        'skill_id',
        'is_verified',
        'email_verified_at',
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
     * Get the user's skill acquisition history.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function skillAcquisitions()
    {
        return $this->hasMany(\App\Models\SkillAcquisitionHistory::class);
    }

    /**
     * Get violations for this user
     */
    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    /**
     * Get active violations for this user
     */
    public function activeViolations()
    {
        return $this->violations()->active();
    }

    /**
     * Check if user is currently suspended
     */
    public function isSuspended()
    {
        $activeSuspension = $this->violations()
            ->active()
            ->suspensions()
            ->where(function($query) {
                $query->where('suspension_duration', 'indefinite')
                      ->orWhere('suspension_duration', 'permanent')
                      ->orWhere('suspension_end', '>', now());
            })
            ->first();

        return $activeSuspension && $activeSuspension->isSuspensionActive();
    }

    /**
     * Check if user is permanently banned
     */
    public function isPermanentlyBanned()
    {
        return $this->violations()
            ->active()
            ->permanentBans()
            ->exists();
    }

    /**
     * Check if user account is restricted (suspended or banned)
     */
    public function isAccountRestricted()
    {
        return $this->isSuspended() || $this->isPermanentlyBanned();
    }

    /**
     * Get current active suspension
     */
    public function getCurrentSuspension()
    {
        return $this->violations()
            ->active()
            ->suspensions()
            ->where(function($query) {
                $query->where('suspension_duration', 'indefinite')
                      ->orWhere('suspension_duration', 'permanent')
                      ->orWhere('suspension_end', '>', now());
            })
            ->first();
    }

    /**
     * Get current active ban
     */
    public function getCurrentBan()
    {
        return $this->violations()
            ->active()
            ->permanentBans()
            ->first();
    }

    /**
     * Get unique skills acquired through trading.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAcquiredSkills()
    {
        try {
            // Only get skills acquired through trading, not manual registration
            $acquisitions = $this->skillAcquisitions()
                ->where('acquisition_method', 'trade_completion')
                ->with('skill')
                ->get();

            // Filter out any acquisitions where skill is null
            $skills = $acquisitions
                ->pluck('skill')
                ->filter() // Remove null values
                ->unique('skill_id')
                ->values();

            return $skills;
        } catch (\Exception $e) {
            \Log::error('Error getting acquired skills for user ' . $this->id . ': ' . $e->getMessage());
            return collect();
        }
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
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

}
