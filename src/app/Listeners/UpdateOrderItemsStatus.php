<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;

class UpdateOrderItemsStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->order->isCanceled()) {
            $event->order->items()->update(['status_key' => 'canceled']);
        }
    }
}
