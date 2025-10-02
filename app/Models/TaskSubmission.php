<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'submitted_by',
        'submission_notes',
        'file_paths',
        'file_types',
        'submitted_at',
        'is_latest'
    ];

    protected $casts = [
        'file_paths' => 'array',
        'submitted_at' => 'datetime',
        'is_latest' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function task()
    {
        return $this->belongsTo(TradeTask::class, 'task_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function evaluation()
    {
        return $this->hasOne(TaskEvaluation::class, 'submission_id');
    }

    /**
     * Scopes
     */
    public function scopeLatest($query)
    {
        return $query->where('is_latest', true);
    }

    public function scopeForTask($query, $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * Accessors & Mutators
     */
    public function getFileUrlsAttribute()
    {
        if (!$this->file_paths) {
            return [];
        }

        return collect($this->file_paths)->map(function ($path) {
            return Storage::url($path);
        })->toArray();
    }

    public function getFileNamesAttribute()
    {
        if (!$this->file_paths) {
            return [];
        }

        return collect($this->file_paths)->map(function ($path) {
            return basename($path);
        })->toArray();
    }

    public function getFileSizesAttribute()
    {
        if (!$this->file_paths) {
            return [];
        }

        return collect($this->file_paths)->map(function ($path) {
            if (Storage::exists($path)) {
                return Storage::size($path);
            }
            return 0;
        })->toArray();
    }

    /**
     * Helper Methods
     */
    public function hasFiles()
    {
        return !empty($this->file_paths);
    }

    public function getFileCount()
    {
        return $this->file_paths ? count($this->file_paths) : 0;
    }

    public function getFormattedFileSize($index = null)
    {
        $sizes = $this->file_sizes;
        
        if ($index !== null) {
            $size = $sizes[$index] ?? 0;
        } else {
            $size = array_sum($sizes);
        }

        return $this->formatBytes($size);
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // When creating a new submission, mark previous ones as not latest
        static::creating(function ($submission) {
            static::where('task_id', $submission->task_id)
                ->update(['is_latest' => false]);
            
            $submission->is_latest = true;
            $submission->submitted_at = now();
        });

        // Clean up files when submission is deleted
        static::deleting(function ($submission) {
            if ($submission->file_paths) {
                foreach ($submission->file_paths as $path) {
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }
                }
            }
        });
    }
}
