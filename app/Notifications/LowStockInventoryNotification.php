<?php

namespace App\Notifications;

use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockInventoryNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly InventoryItem $item)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inventory_low_stock',
            'item_id' => $this->item->id,
            'item_name' => $this->item->name,
            'internal_code' => $this->item->internal_code,
            'current_quantity' => $this->item->quantity,
            'threshold' => $this->item->low_stock_threshold,
            'message' => sprintf(
                'Escasez detectada: %s (%s) con %d unidades.',
                $this->item->name,
                $this->item->internal_code,
                $this->item->quantity
            ),
        ];
    }
}
