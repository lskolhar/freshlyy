<?php

namespace App\Models;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'total_amount',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Order belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Order has many OrderItems
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
