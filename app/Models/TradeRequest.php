<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeRequest extends Model
{
    use HasFactory;

    protected $fillable = ['trade_id','requester_id','status','responded_at','message'];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function trade() { return $this->belongsTo(Trade::class); }
    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
}


