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
        'grade',
        'status',
        'feedback',
        'skills_to_add',
        'skills_added',
        'evaluated_at',
        'viewed_at'
    ];

    protected $casts = [
        'skills_to_add' => 'array',
        'skills_added' => 'boolean',
        'evaluated_at' => 'datetime',
        'viewed_at' => 'datetime',
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

    /**
     * Get grade letter (for backward compatibility with old records)
     * Returns the stored grade if exists, otherwise returns score percentage
     */
    public function getGradeLetterAttribute()
    {
        // For backward compatibility: return stored grade if exists
        if ($this->grade) {
            return $this->grade;
        }
        
        // For new records: return score percentage as string
        if ($this->score_percentage !== null) {
            return (string) $this->score_percentage;
        }
        
        return 'N/A';
    }
    
    /**
     * Get checked_at date (alias for evaluated_at for UI clarity)
     */
    public function getCheckedAtAttribute()
    {
        return $this->evaluated_at;
    }
    
    /**
     * Check if submission has been viewed by evaluator
     */
    public function hasBeenViewed()
    {
        return !is_null($this->viewed_at);
    }
    
    /**
     * Check if task has been graded
     */
    public function hasBeenGraded()
    {
        return !is_null($this->evaluated_at) && !is_null($this->score_percentage);
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
            if (!$evaluation->evaluated_at) {
                $evaluation->evaluated_at = now();
            }
            
            // Auto-calculate status from score_percentage if not set
            if ($evaluation->score_percentage !== null && !$evaluation->status) {
                $evaluation->status = $evaluation->score_percentage >= 70 ? 'pass' : 'fail';
            }
        });
        
        static::updating(function ($evaluation) {
            // Auto-calculate status from score_percentage if score changed and status not explicitly set
            if ($evaluation->isDirty('score_percentage') && !$evaluation->isDirty('status')) {
                $evaluation->status = $evaluation->score_percentage >= 70 ? 'pass' : 'fail';
            }
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
