<?php

namespace App\Models\Logs;

use App\Models\Orders\Order;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Logs\OrderActionLog
 *
 * @property int $id
 * @property int $order_id
 * @property int $admin_id
 * @property string $action
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read Order $order
 * @property-read Administrator $admin
 */
class OrderActionLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'log_order_actions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'admin_id',
        'action',
    ];

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    public const UPDATED_AT = null;

    /**
     * Get the order associated with the action log.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the admin associated with the action log.
     */
    public function admin()
    {
        return $this->belongsTo(Administrator::class);
    }

    /**
     * Return order tracked fields for logging
     */
    public static function getTrackedFields(): array
    {
        return [
            'last_name' => 'Имя',
            'first_name' => 'Фамилия',
            'patronymic_name' => 'Отчество',
            'phone' => 'Телефон',
            'email' => 'Email',
            'country_id' => 'id страны',
            'region' => 'Область',
            'city' => 'Город',
            'zip' => 'ZIP',
            'user_addr' => 'Адрес',
            'weight' => 'Вес',
        ];
    }

    /**
     * Farmat date in admin panel
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('d.m.Y H:i:s');
    }
}
