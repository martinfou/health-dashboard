<?php
namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class TradingController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        
        $trades = Trade::where('user_id', $userId)->orderBy('entry_time', 'desc')->limit(20)->get();
        
        $openTrades = Trade::where('user_id', $userId)->where('status', 'OPEN')->get();
        $totalPnl = Trade::where('user_id', $userId)->sum('pnl');
        
        // Fetch OANDA prices
        $prices = $this->fetchPrices();
        
        return Inertia::render('Trading', [
            'trades' => $trades,
            'openTrades' => $openTrades,
            'totalPnl' => $totalPnl,
            'prices' => $prices,
            'accountBalance' => $this->fetchAccountInfo(),
        ]);
    }
    
    private function fetchPrices()
    {
        $pairs = ['EUR_USD', 'GBP_USD', 'USD_CAD', 'AUD_USD', 'GBP_JPY', 'USD_CHF'];
        try {
            $response = Http::withToken(config('services.oanda.token'))
                ->get('https://api-fxpractice.oanda.com/v3/accounts/' . config('services.oanda.account_id') . '/pricing', [
                    'instruments' => implode(',', $pairs)
                ]);
            if ($response->successful()) {
                $prices = [];
                foreach ($response->json()['prices'] ?? [] as $p) {
                    $prices[$p['instrument']] = [
                        'bid' => $p['bids'][0]['price'],
                        'ask' => $p['asks'][0]['price'],
                        'spread' => round((float)$p['asks'][0]['price'] - (float)$p['bids'][0]['price'], 5)
                    ];
                }
                return $prices;
            }
        } catch (\Exception $e) {}
        
        return [
            'EUR_USD' => ['bid' => '—', 'ask' => '—', 'spread' => '—'],
            'GBP_USD' => ['bid' => '—', 'ask' => '—', 'spread' => '—'],
            'USD_CAD' => ['bid' => '—', 'ask' => '—', 'spread' => '—'],
            'AUD_USD' => ['bid' => '—', 'ask' => '—', 'spread' => '—'],
            'GBP_JPY' => ['bid' => '—', 'ask' => '—', 'spread' => '—'],
            'USD_CHF' => ['bid' => '—', 'ask' => '—', 'spread' => '—'],
        ];
    }
    
    private function fetchAccountInfo()
    {
        try {
            $response = Http::withToken(config('services.oanda.token'))
                ->get('https://api-fxpractice.oanda.com/v3/accounts/' . config('services.oanda.account_id') . '/summary');
            if ($response->successful()) {
                $a = $response->json()['account'];
                return [
                    'balance' => $a['balance'],
                    'pl' => $a['unrealizedPL'] ?? 0,
                    'trades' => $a['openTradeCount'] ?? 0,
                ];
            }
        } catch (\Exception $e) {}
        return ['balance' => '—', 'pl' => 0, 'trades' => 0];
    }
}
