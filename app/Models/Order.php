<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_name',
        'type',
        'promocode_id',
        'email',
        'phone',
        'comment',
        'currency',
        'rate',
        'source',
        'country',
        'region',
        'city',
        'zip',
        'street',
        'house',
        'user_addr',
        'payment',
        'payment_code',
        'payment_cost',
        'delivery',
        'delivery_code',
        'delivery_cost',
        'delivery_point',
        'delivery_point_code',
        'user_id',
    ];
    /**
     * Товары заказа
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function data()
    {
        return $this->hasMany(OrderData::class);
    }
}
