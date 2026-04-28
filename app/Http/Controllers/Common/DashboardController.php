<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use App\Services\Dashboard\DashboardService;
use Illuminate\View\View;

class DashboardController
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(): View
    {
        return view('dashboard', [
            'kpis'               => $this->dashboard->getKpis(),
            'activities'         => $this->dashboard->getRecentActivities(),
            'alerts'             => $this->dashboard->getAlerts(),
            'statusStats'        => $this->dashboard->getStatusStats(),
            'indexingStats'      => $this->dashboard->getIndexingStats(),
            'activeInstitutions' => $this->dashboard->getActiveInstitutions(),
            'popularTags'        => $this->dashboard->getPopularTags(),
        ]);
    }
}
