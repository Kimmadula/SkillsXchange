<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Violation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_id',
        'violation_type',
        'suspension_duration',
        'reason',
        'admin_notes',
        'suspension_start',
        'suspension_end',
        'is_active',
    ];

    protected $casts = [
        'suspension_start' => 'datetime',
        'suspension_end' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who received the violation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who issued the violation
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Check if the suspension is still active
     */
    public function isSuspensionActive()
    {
        if (!$this->is_active || $this->violation_type !== 'suspension') {
            return false;
        }

        if ($this->suspension_duration === 'permanent') {
            return true;
        }

        if ($this->suspension_end && $this->suspension_end->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if user is permanently banned
     */
    public function isPermanentBan()
    {
        return $this->violation_type === 'permanent_ban' && $this->is_active;
    }

    /**
     * Get suspension duration in days
     */
    public function getSuspensionDays()
    {
        switch ($this->suspension_duration) {
            case '7_days':
                return 7;
            case '30_days':
                return 30;
            case 'indefinite':
            case 'permanent':
                return null; // No end date
            default:
                return 0;
        }
    }

    /**
     * Calculate suspension end date
     */
    public function calculateSuspensionEnd()
    {
        if (!$this->suspension_start) {
            return null;
        }

        $days = $this->getSuspensionDays();
        if ($days === null) {
            return null; // Indefinite or permanent
        }

        return $this->suspension_start->addDays($days);
    }

    /**
     * Scope for active violations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for suspensions
     */
    public function scopeSuspensions($query)
    {
        return $query->where('violation_type', 'suspension');
    }

    /**
     * Scope for permanent bans
     */
    public function scopePermanentBans($query)
    {
        return $query->where('violation_type', 'permanent_ban');
    }
}