<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'rater_id',
        'rated_user_id',
        'session_type',
        'overall_rating',
        'communication_rating',
        'helpfulness_rating',
        'knowledge_rating',
        'written_feedback',
        'session_duration',
        'skills_discussed',
    ];

    protected $casts = [
        'skills_discussed' => 'array',
    ];

    /**
     * Get the user who gave the rating
     */
    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    /**
     * Get the user who was rated
     */
    public function ratedUser()
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    /**
     * Get the trade associated with this rating
     */
    public function trade()
    {
        return $this->belongsTo(Trade::class, 'session_id');
    }
}


