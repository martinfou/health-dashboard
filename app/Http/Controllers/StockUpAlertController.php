<?php

namespace App\Http\Controllers;

use App\Services\StockUpAlertService;
use Illuminate\Http\Request;

class StockUpAlertController extends Controller
{
    public function __construct(protected StockUpAlertService $alertService) {}

    public function index()
    {
        $alerts = $this->alertService->getRecentAlerts(50);
        return view('grocery.stock-up', compact('alerts'));
    }

    public function trigger(Request $request)
    {
        $created = $this->alertService->checkAlerts();
        $notified = $this->alertService->sendNotifications();

        return redirect()->route('grocery.stock-up')
            ->with('success', "{$created} alertes créées, {$notified} notifications envoyées.");
    }
}
