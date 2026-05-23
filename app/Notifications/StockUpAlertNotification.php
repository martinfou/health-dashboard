<?php

namespace App\Notifications;

use App\Models\StockUpAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockUpAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public StockUpAlert $alert) {}

    public function via($notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram($notifiable)
    {
        $storeName = $this->alert->store->name ?? 'inconnu';
        $savings = $this->alert->historical_low_price - $this->alert->price;

        return "🚨 STOCK UP: {$this->alert->product} à {$this->alert->price}\$ chez {$storeName} — prix le plus bas depuis 6 mois! (Économie: {$savings}\$)";
    }
}
