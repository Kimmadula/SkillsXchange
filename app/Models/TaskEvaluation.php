<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'submission_id',
        'evaluated_by',
        'score_percentage',
        'status',
        'feedback',
        'improvement_notes',
        'skills_to_add',
        'skills_added',
        'evaluated_at'
    ];

    protected $casts = [
        'skills_to_add' => 'array',
        'skills_added' => 'boolean',
        'evaluated_at' => 'datetime',
        'score_percentage' => 'integer'
    ];

    /**
     * Relationships
     */
    public function task()
    {
        return $this->belongsTo(TradeTask::class, 'task_id');
    }

    public function submission()
    {
        return $this->belongsTo(TaskSubmission::class, 'submission_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * Scopes
     */
    public function scopePassed($query)
    {
        return $query->where('status', 'pass');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'fail');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNeedsImprovement($query)
    {
        return $query->where('status', 'needs_improvement');
    }

    /**
     * Accessors & Mutators
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pass' => 'success',
            'fail' => 'danger',
            'needs_improvement' => 'warning',
            'pending' => 'secondary',
            default => 'secondary'
        };
    }

    public function getStatusIconAttribute()
    {
        return match($this->status) {
            'pass' => 'fas fa-check-circle',
            'fail' => 'fas fa-times-circle',
            'needs_improvement' => 'fas fa-exclamation-triangle',
            'pending' => 'fas fa-clock',
            default => 'fas fa-question-circle'
        };
    }

    public function getGradeLetterAttribute()
    {
        if (!$this->score_percentage) {
            return 'N/A';
        }

        return match(true) {
            $this->score_percentage >= 95 => 'A+',
            $this->score_percentage >= 90 => 'A',
            $this->score_percentage >= 85 => 'A-',
            $this->score_percentage >= 80 => 'B+',
            $this->score_percentage >= 75 => 'B',
            $this->score_percentage >= 70 => 'B-',
            $this->score_percentage >= 65 => 'C+',
            $this->score_percentage >= 60 => 'C',
            $this->score_percentage >= 55 => 'C-',
            $this->score_percentage >= 50 => 'D',
            default => 'F'
        };
    }

    /**
     * Helper Methods
     */
    public function isPassed()
    {
        return $this->status === 'pass';
    }

    public function isFailed()
    {
        return $this->status === 'fail';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function needsImprovement()
    {
        return $this->status === 'needs_improvement';
    }

    public function hasSkillsToAdd()
    {
        return !empty($this->skills_to_add);
    }

    public function getSkillsToAddNames()
    {
        if (!$this->hasSkillsToAdd()) {
            return [];
        }

        return Skill::whereIn('skill_id', $this->skills_to_add)
            ->pluck('name')
            ->toArray();
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evaluation) {
            $evaluation->evaluated_at = now();
        });

        static::updating(function ($evaluation) {
            // If status changed to pass and skills haven't been added yet
            if ($evaluation->isDirty('status') && 
                $evaluation->status === 'pass' && 
                !$evaluation->skills_added && 
                $evaluation->hasSkillsToAdd()) {
                
                // This will be handled by the service layer
                // Mark for skill addition processing
            }
        });
    }
}
