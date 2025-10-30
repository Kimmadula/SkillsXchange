<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','offering_skill_id','looking_skill_id','start_date','end_date','available_from','available_to','preferred_days','gender_pref','location','session_type','use_username','status'
    ];

    // Sensible defaults to avoid null/truthy confusion in views
    protected $attributes = [
        'use_username' => false,
        'preferred_days' => '[]',
    ];

    protected $casts = [
        'preferred_days' => 'array',
        'use_username' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // The user who created this trade (offering a skill)
    public function user() { return $this->belongsTo(User::class); }
    public function offeringUser() { return $this->belongsTo(User::class, 'user_id'); }

    // Skills
    public function offeringSkill() { return $this->belongsTo(Skill::class, 'offering_skill_id', 'skill_id'); }
    public function lookingSkill() { return $this->belongsTo(Skill::class, 'looking_skill_id', 'skill_id'); }

    // Related models
    public function requests() { return $this->hasMany(TradeRequest::class); }
    public function messages() { return $this->hasMany(TradeMessage::class); }
    public function tasks() { return $this->hasMany(TradeTask::class); }

    // Session expiration methods
    public function isExpired()
    {
        $now = \Carbon\Carbon::now();

        // Check if end date has passed
        if ($this->end_date && $this->end_date < $now->toDateString()) {
            return true;
        }

        // Check if it's the end date and time has passed
        if ($this->end_date && $this->end_date == $now->toDateString() &&
            $this->available_to && $this->available_to < $now->toTimeString()) {
            return true;
        }

        return false;
    }

    public function isActive()
    {
        return $this->status === 'ongoing' && !$this->isExpired();
    }

    public function getSessionStatus()
    {
        if ($this->status === 'closed') {
            return 'expired';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->status === 'ongoing') {
            return 'active';
        }

        return 'inactive';
    }
}


