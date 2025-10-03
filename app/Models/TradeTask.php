<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'completed',
        'completed_at',
        'verified',
        'verified_at',
        'verified_by',
        'verification_notes',
        'priority',
        'due_date',
        'associated_skills',
        'requires_submission',
        'submission_type',
        'submission_instructions',
        'max_score',
        'passing_score',
        'current_status',
        'started_at',
        'submitted_at',
        'evaluated_at',
        'allowed_file_types',
        'strict_file_types'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'due_date' => 'datetime',
        'associated_skills' => 'array',
        'requires_submission' => 'boolean',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'max_score' => 'integer',
        'passing_score' => 'integer',
        'allowed_file_types' => 'array',
        'strict_file_types' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function submissions()
    {
        return $this->hasMany(TaskSubmission::class, 'task_id');
    }

    public function latestSubmission()
    {
        return $this->hasOne(TaskSubmission::class, 'task_id')->where('is_latest', true);
    }

    public function evaluations()
    {
        return $this->hasMany(TaskEvaluation::class, 'task_id');
    }

    public function latestEvaluation()
    {
        return $this->hasOne(TaskEvaluation::class, 'task_id')->latest('evaluated_at');
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('current_status', $status);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeRequiringSubmission($query)
    {
        return $query->where('requires_submission', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('current_status', ['completed', 'evaluated']);
    }

    /**
     * Accessors & Mutators
     */
    public function getStatusColorAttribute()
    {
        return match($this->current_status) {
            'assigned' => 'secondary',
            'in_progress' => 'info',
            'submitted' => 'warning',
            'evaluated' => 'primary',
            'completed' => 'success',
            default => 'secondary'
        };
    }

    public function getStatusIconAttribute()
    {
        return match($this->current_status) {
            'assigned' => 'fas fa-clipboard-list',
            'in_progress' => 'fas fa-spinner',
            'submitted' => 'fas fa-upload',
            'evaluated' => 'fas fa-check-square',
            'completed' => 'fas fa-check-circle',
            default => 'fas fa-question-circle'
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'success',
            default => 'secondary'
        };
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->current_status, ['completed', 'evaluated']);
    }

    public function getDaysUntilDueAttribute()
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Helper Methods
     */
    public function canBeStarted()
    {
        return $this->current_status === 'assigned';
    }

    public function canBeSubmitted()
    {
        return $this->current_status === 'in_progress' && $this->requires_submission;
    }

    public function canBeEvaluated()
    {
        return $this->current_status === 'submitted';
    }

    public function isCompleted()
    {
        return in_array($this->current_status, ['completed', 'evaluated']);
    }

    public function hasAssociatedSkills()
    {
        return !empty($this->associated_skills);
    }

    public function getAssociatedSkillNames()
    {
        if (!$this->hasAssociatedSkills()) {
            return [];
        }

        return Skill::whereIn('skill_id', $this->associated_skills)
            ->pluck('name')
            ->toArray();
    }

    public function hasAllowedFileTypes()
    {
        return !empty($this->allowed_file_types);
    }

    public function getAllowedFileTypesAttribute()
    {
        return $this->attributes['allowed_file_types'] ? json_decode($this->attributes['allowed_file_types'], true) : [];
    }

    public function isFileTypeAllowed($fileType)
    {
        if (!$this->hasAllowedFileTypes()) {
            return true; // Allow all types if none specified
        }

        return in_array($fileType, $this->allowed_file_types);
    }

    public function validateFileType($mimeType, $extension = null)
    {
        if (!$this->hasAllowedFileTypes()) {
            return true; // Allow all types if none specified
        }

        $allowedTypes = $this->allowed_file_types;
        
        foreach ($allowedTypes as $type) {
            switch ($type) {
                case 'image':
                    if (str_starts_with($mimeType, 'image/')) {
                        return true;
                    }
                    break;
                case 'video':
                    if (str_starts_with($mimeType, 'video/')) {
                        return true;
                    }
                    break;
                case 'pdf':
                    if ($mimeType === 'application/pdf') {
                        return true;
                    }
                    break;
                case 'word':
                    if (in_array($mimeType, [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ])) {
                        return true;
                    }
                    break;
                case 'excel':
                    if (in_array($mimeType, [
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ])) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    public function getFileTypeValidationRules()
    {
        if (!$this->hasAllowedFileTypes()) {
            return 'file|max:50000'; // Default validation
        }

        $mimeTypes = [];
        $extensions = [];

        foreach ($this->allowed_file_types as $type) {
            switch ($type) {
                case 'image':
                    $mimeTypes = array_merge($mimeTypes, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
                    $extensions = array_merge($extensions, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    break;
                case 'video':
                    $mimeTypes = array_merge($mimeTypes, ['video/mp4', 'video/quicktime', 'video/x-msvideo']);
                    $extensions = array_merge($extensions, ['mp4', 'mov', 'avi']);
                    break;
                case 'pdf':
                    $mimeTypes[] = 'application/pdf';
                    $extensions[] = 'pdf';
                    break;
                case 'word':
                    $mimeTypes = array_merge($mimeTypes, [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ]);
                    $extensions = array_merge($extensions, ['doc', 'docx']);
                    break;
                case 'excel':
                    $mimeTypes = array_merge($mimeTypes, [
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ]);
                    $extensions = array_merge($extensions, ['xls', 'xlsx']);
                    break;
            }
        }

        $mimeTypesString = implode(',', array_unique($mimeTypes));
        $extensionsString = implode(',', array_unique($extensions));

        // If no valid types found, return basic file validation
        if (empty($mimeTypesString) || empty($extensionsString)) {
            return 'file|max:50000';
        }

        return "file|max:50000|mimes:{$extensionsString}";
    }

    public function getAllowedFileTypesDisplay()
    {
        if (!$this->hasAllowedFileTypes()) {
            return 'All file types allowed';
        }

        $typeLabels = [
            'image' => 'Images (JPG, PNG, GIF)',
            'video' => 'Videos (MP4, MOV, AVI)',
            'pdf' => 'PDF Documents',
            'word' => 'Word Documents (DOC, DOCX)',
            'excel' => 'Excel Files (XLS, XLSX)'
        ];

        $displayTypes = [];
        foreach ($this->allowed_file_types as $type) {
            if (isset($typeLabels[$type])) {
                $displayTypes[] = $typeLabels[$type];
            }
        }

        return implode(', ', $displayTypes);
    }

    public function getAcceptAttribute()
    {
        if (!$this->hasAllowedFileTypes()) {
            return '*';
        }

        $acceptTypes = [];
        foreach ($this->allowed_file_types as $type) {
            switch ($type) {
                case 'image':
                    $acceptTypes = array_merge($acceptTypes, ['image/*', '.jpg', '.jpeg', '.png', '.gif', '.webp']);
                    break;
                case 'video':
                    $acceptTypes = array_merge($acceptTypes, ['video/*', '.mp4', '.mov', '.avi']);
                    break;
                case 'pdf':
                    $acceptTypes = array_merge($acceptTypes, ['application/pdf', '.pdf']);
                    break;
                case 'word':
                    $acceptTypes = array_merge($acceptTypes, [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        '.doc', '.docx'
                    ]);
                    break;
                case 'excel':
                    $acceptTypes = array_merge($acceptTypes, [
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        '.xls', '.xlsx'
                    ]);
                    break;
            }
        }

        return implode(',', array_unique($acceptTypes));
    }

    public function updateStatus($newStatus)
    {
        $this->current_status = $newStatus;
        
        switch ($newStatus) {
            case 'in_progress':
                $this->started_at = now();
                break;
            case 'submitted':
                $this->submitted_at = now();
                break;
            case 'evaluated':
            case 'completed':
                $this->evaluated_at = now();
                $this->completed = true;
                $this->completed_at = now();
                break;
        }
        
        $this->save();
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (!$task->current_status) {
                $task->current_status = 'assigned';
            }
            if (!$task->max_score) {
                $task->max_score = 100;
            }
            if (!$task->passing_score) {
                $task->passing_score = 70;
            }
        });
    }
}
