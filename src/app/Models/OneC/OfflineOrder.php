<?php

namespace App\Models\OneC;

use App\Admin\Models\AvailableSizesFull;
use App\Models\Product;
use App\Models\Size;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * @property string $ID
 * @property string $CODE
 * @property string $DESCR object description
 * @property string $ISMARK Flag Object is Market
 * @property string $VERSTAMP Version stamp
 * @property string $SP6089 ДисконтнаяКарта
 * @property string $SP6090 КодДК
 * @property string $SP6091 Товар
 * @property int $SP6092 КодТовара
 * @property string $SP6093 Артикул
 * @property string $SP6094 НаименованиеТовар
 * @property string $SP6095 Магазин
 * @property int $SP6096 КодМагазина
 * @property string $SP6097 ДатаПродажи
 * @property string $SP6098 НомерЧека
 * @property string $SP6099 Количество
 * @property int $SP6100 Размер
 * @property float $SP6101 Сумма
 * @property string $SP6102 Телефон
 * @property string $SP6107 ВремяПродаж
 * @property string $SP6108 ДатаВозврата
 * @property string $SP6109 ВремяВозврата
 * @property string $SP6110 НомерВозврата
 *
 * @property-read Stock|null $stock
 * @property-read Product|null $product
 * @property-read Size|null $size
 */
class OfflineOrder extends AbstractOneCModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'SC6104';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'CODE' => 'integer',
        'SP6092' => 'integer',
        'SP6096' => 'integer',
        'SP6098' => 'string',
        'SP6100' => 'integer',
        'SP6101' => 'float',
    ];

    /**
     * Get the latest code by receipt number from the offline orders.
     */
    public static function getLatestCodeByReceiptNumber(?string $receiptNumber): int
    {
        return (int)self::query()->where('SP6098', $receiptNumber)->value('CODE');
    }

    /**
     * Check if the order is a return.
     */
    public function isReturn(): bool
    {
        return !empty($this->SP6109);
    }

    /**
     * Get the stock associated with the offline order.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'SP6096', 'one_c_id');
    }

    /**
     * Get the available sizes associated with the offline order.
     */
    public function availableSizes(): HasMany
    {
        return $this->hasMany(AvailableSizesFull::class, 'one_c_product_id', 'SP6092');
    }

    /**
     * Get the product associated with the offline order.
     */
    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(
            Product::class,
            AvailableSizesFull::class,
            'one_c_product_id',
            'id',
            'SP6092',
            'product_id'
        )->withTrashed();
    }

    /**
     * Get the size associated with the offline order.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class, 'SP6100', 'name');
    }

    /**
     * Get the date and time when the order was sold.
     */
    public function getSoldAtDateTime(): Carbon
    {
        $date = Carbon::parse($this->SP6097);

        return $date->setTimeFromTimeString($this->SP6107);
    }

    /**
     * Get the date and time when the order was returned.
     */
    public function getReturnedAtDateTime(): Carbon
    {
        $date = Carbon::parse($this->SP6108);

        return $date->setTimeFromTimeString($this->SP6109);
    }

    /**
     * Get the formatted offline order phone number.
     */
    public function getFormattedPhone(): ?string
    {
        $phone = $this->SP6102;
        if (!$phone || strlen($phone) < 7) {
            return null;
        }
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();

            $parsedPhone = $phoneUtil->parse($phone, 'BY');
            if ($phoneUtil->isValidNumber($parsedPhone)) {
                return $phoneUtil->format($parsedPhone, PhoneNumberFormat::E164);
            }
        } catch (\Throwable $th) {
        }

        return null;
    }
}
