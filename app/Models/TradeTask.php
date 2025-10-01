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
        'due_date'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'due_date' => 'datetime'
    ];

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
}
