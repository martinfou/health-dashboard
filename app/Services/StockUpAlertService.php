<?php

namespace App\Services;

use App\Models\GroceryDeal;
use App\Models\PriceHistory;
use App\Models\StockUpAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class StockUpAlertService
{
    /**
     * Check current deals against historical prices and create alerts
     * when a deal is within 5% of the historical low.
     */
    public function checkAlerts(): int
    {
        $alertsCreated = 0;

        $currentDeals = GroceryDeal::current()->with('store')->get();

        foreach ($currentDeals as $deal) {
            // Find historical low price for the same product+store over last 6 months
            $historicalLow = PriceHistory::where('grocery_store_id', $deal->grocery_store_id)
                ->where('product', $deal->product)
                ->where('scraped_at', '>=', now()->subMonths(6))
                ->min('sale_price');

            if ($historicalLow === null || $historicalLow == 0) {
                continue;
            }

            // Check if current price is within 5% of historical low
            $savingsPct = (($historicalLow - $deal->price) / $historicalLow) * 100;
            $threshold = abs($savingsPct);

            if ($threshold <= 5.0) {
                // Avoid duplicate alerts for the same deal
                $existing = StockUpAlert::where('grocery_deal_id', $deal->id)->first();
                if (!$existing) {
                    StockUpAlert::create([
                        'grocery_deal_id' => $deal->id,
                        'product' => $deal->product,
                        'grocery_store_id' => $deal->grocery_store_id,
                        'price' => $deal->price,
                        'historical_low_price' => $historicalLow,
                        'savings_pct' => $savingsPct,
                        'triggered_at' => now(),
                    ]);
                    $alertsCreated++;
                }
            }
        }

        return $alertsCreated;
    }

    /**
     * Send notifications for untriggered alerts via the notification channel.
     */
    public function sendNotifications(): int
    {
        $alerts = StockUpAlert::notNotified()->with('store', 'deal')->get();
        $count = 0;

        foreach ($alerts as $alert) {
            Notification::route('telegram', config('services.telegram-bot-api.chat_id'))
                ->notify(new \App\Notifications\StockUpAlertNotification($alert));

            $alert->update(['notified_at' => now()]);
            $count++;
        }

        return $count;
    }

    /**
     * Get recent alerts for display.
     */
    public function getRecentAlerts(int $limit = 50)
    {
        return StockUpAlert::with('store', 'deal')
            ->orderBy('triggered_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
