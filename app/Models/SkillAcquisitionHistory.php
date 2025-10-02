<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillAcquisitionHistory extends Model
{
    use HasFactory;

    protected $table = 'skill_acquisition_history';

    protected $fillable = [
        'user_id',
        'skill_id',
        'task_id',
        'trade_id',
        'acquisition_method',
        'notes',
        'score_achieved',
        'acquired_at'
    ];

    protected $casts = [
        'acquired_at' => 'datetime',
        'score_achieved' => 'integer'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'skill_id', 'skill_id');
    }

    public function task()
    {
        return $this->belongsTo(TradeTask::class, 'task_id');
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class, 'trade_id');
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('acquisition_method', $method);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('acquired_at', '>=', now()->subDays($days));
    }

    /**
     * Accessors
     */
    public function getMethodDisplayAttribute()
    {
        return match($this->acquisition_method) {
            'task_completion' => 'Task Completion',
            'manual_add' => 'Manual Addition',
            'trade_completion' => 'Trade Completion',
            'verification' => 'Skill Verification',
            default => 'Unknown'
        };
    }

    public function getMethodIconAttribute()
    {
        return match($this->acquisition_method) {
            'task_completion' => 'fas fa-tasks',
            'manual_add' => 'fas fa-plus-circle',
            'trade_completion' => 'fas fa-handshake',
            'verification' => 'fas fa-check-circle',
            default => 'fas fa-question-circle'
        };
    }

    public function getMethodColorAttribute()
    {
        return match($this->acquisition_method) {
            'task_completion' => 'success',
            'manual_add' => 'primary',
            'trade_completion' => 'info',
            'verification' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Helper Methods
     */
    public static function recordSkillAcquisition($userId, $skillId, $method = 'task_completion', $options = [])
    {
        return self::create([
            'user_id' => $userId,
            'skill_id' => $skillId,
            'task_id' => $options['task_id'] ?? null,
            'trade_id' => $options['trade_id'] ?? null,
            'acquisition_method' => $method,
            'notes' => $options['notes'] ?? null,
            'score_achieved' => $options['score_achieved'] ?? null,
            'acquired_at' => now()
        ]);
    }

    public static function getUserSkillHistory($userId, $limit = null)
    {
        $query = self::forUser($userId)
            ->with(['skill', 'task', 'trade'])
            ->orderBy('acquired_at', 'desc');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    public static function getSkillStats($userId)
    {
        $history = self::forUser($userId);
        
        return [
            'total_skills' => $history->count(),
            'this_month' => $history->where('acquired_at', '>=', now()->startOfMonth())->count(),
            'this_year' => $history->where('acquired_at', '>=', now()->startOfYear())->count(),
            'by_method' => $history->groupBy('acquisition_method')
                ->map(function ($group) {
                    return $group->count();
                })->toArray(),
            'recent_acquisitions' => $history->recent(7)->count()
        ];
    }
}
