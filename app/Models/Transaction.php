<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'order_id',
        'reference_id',
        'gateway_transaction_id',
        'amount',
        'status',
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}