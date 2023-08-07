<?php

namespace App\Services\Order;

use App\Models\Orders\OrderItem;
use App\Notifications\OrderItemInventoryNotification;

/**
 * Class OrderItemInventoryService
 * @package App\Services\Order
 */
class OrderItemInventoryService
{
    /**
     * Possible statuses for which notifications are sent.
     */
    const STATUSES_FOR_NOTIFICATIONS = [
        'new',
        'canceled',
        'confirmed',
        'complete',
        'installment',
        'return',
        'return_fitting',
    ];

    /**
     * Handle the change of status for an order item and send notification if required.
     */
    public function handleChangeItemStatus(OrderItem $orderItem): void
    {
        if ($this->shouldSendNotification($orderItem)) {
            $chat = $orderItem->invertoryNotification->stock->chat;
            $chat->notify(new OrderItemInventoryNotification($orderItem));
            $orderItem->invertoryNotification->setDateFieldForStatus($orderItem->status_key);
        }
    }

    /**
     * Check if a notification should be sent for the given order item and status.
     */
    protected function shouldSendNotification(OrderItem $orderItem): bool
    {
        $notification = $orderItem->invertoryNotification;
        if (empty($notification) || empty($notification->stock->chat)) {
            return false;
        }

        $status = $orderItem->status_key;
        $dateField = $notification::getDateFieldByStatus($status);

        return in_array($status, self::STATUSES_FOR_NOTIFICATIONS) && is_null($notification->{$dateField});
    }
}
