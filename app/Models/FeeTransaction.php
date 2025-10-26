<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fee_type',
        'amount',
        'trade_id',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    /**
     * Get the user that owns the fee transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the trade associated with this fee transaction
     */
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for specific fee type
     */
    public function scopeByFeeType($query, $feeType)
    {
        return $query->where('fee_type', $feeType);
    }

    /**
     * Scope for trade-related fees
     */
    public function scopeTradeFees($query)
    {
        return $query->whereNotNull('trade_id');
    }
}
