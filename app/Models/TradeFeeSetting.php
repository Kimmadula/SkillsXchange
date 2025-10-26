<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeFeeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_type',
        'fee_amount',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fee_amount' => 'integer',
    ];

    /**
     * Get fee amount for a specific fee type
     */
    public static function getFeeAmount($feeType)
    {
        $setting = self::where('fee_type', $feeType)
                      ->where('is_active', true)
                      ->first();

        return $setting ? $setting->fee_amount : 0;
    }

    /**
     * Check if a fee type is active
     */
    public static function isFeeActive($feeType)
    {
        return self::where('fee_type', $feeType)
                  ->where('is_active', true)
                  ->exists();
    }

    /**
     * Get all active fee settings
     */
    public static function getActiveFees()
    {
        return self::where('is_active', true)->get();
    }

    /**
     * Scope for active fees
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific fee type
     */
    public function scopeByType($query, $feeType)
    {
        return $query->where('fee_type', $feeType);
    }
}
