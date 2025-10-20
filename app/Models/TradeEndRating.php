<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeEndRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_id',
        'rater_id',
        'rated_user_id',
        'service_quality_rating',
        'communication_rating',
        'timeliness_rating',
        'value_rating',
        'overall_experience_rating',
        'written_feedback',
        'would_recommend',
        'trade_completion_satisfaction',
    ];
}


