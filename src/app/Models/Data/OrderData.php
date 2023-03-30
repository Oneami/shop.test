<?php

namespace App\Models\Data;

use App\Models\User\User;
use Deliveries\DeliveryMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Payments\PaymentMethod;

class OrderData
{
    /**
     * The user who make the order
     */
    public readonly User $user;

    /**
     * Order payment method
     */
    public readonly PaymentMethod $paymentMethod;

    /**
     * Order delivery method
     */
    public readonly DeliveryMethod $deliveryMethod;

    /**
     * Order creation date
     */
    public readonly ?Carbon $created_at;

    /**
     * OrderData constructor
     */
    public function __construct(
        public ?int $user_id,
        public string $first_name,
        public string $phone,
        public ?string $user_addr,
        public string $order_method,
        public string $currency,
        public float $rate,
        public ?string $utm_medium,
        public ?string $utm_source,
        public ?string $utm_campaign,
        public ?string $utm_content,
        public ?string $utm_term,
        public ?string $patronymic_name = null,
        public ?string $last_name = null,
        public ?string $email = null,
        public ?string $comment = null,
        public ?int $payment_id = null,
        public ?int $delivery_id = null,
        public ?int $country_id = null,
        public ?string $city = null,
        public float $total_price = 0,
        public ?string $status_key = null,
        ?string $created_at = null,
        ...$otherData
    ) {
        $this->user = $this->findModel(new User(), $this->user_id);
        $this->paymentMethod = $this->findModel(new PaymentMethod(), $this->payment_id);
        $this->deliveryMethod = $this->findModel(new DeliveryMethod(), $this->delivery_id);
        $this->created_at = $this->createDate($created_at);
    }

    /**
     * Find order property model
     * TODO: create a separate trait
     */
    private function findModel(Model $model, ?int $id): Model
    {
        return $id ? $model->query()->findOrNew($id) : new $model();
    }

    /**
     * Create a new Carbon instance.
     * TODO: create a separate trait
     */
    public function createDate(\DateTimeInterface|string|null $date): ?Carbon
    {
        return $date ? new Carbon($date) : null;
    }

    /**
     * Prepare order data to save
     */
    public function prepareToSave(): array
    {
        return array_filter((array)$this);
    }
}