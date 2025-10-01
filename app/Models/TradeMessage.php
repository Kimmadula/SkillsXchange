<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_id',
        'sender_id',
        'message'
    ];

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
