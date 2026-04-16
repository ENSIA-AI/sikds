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
            'popularTags'        => [
                ['label' => 'Directive',  'class' => 'sikds-tag--directive'],
                ['label' => 'Urgent',     'class' => 'sikds-tag--urgent'],
                ['label' => 'Régulation', 'class' => 'sikds-tag--reg'],
                ['label' => 'Rapport',    'class' => 'sikds-tag--rapport'],
                ['label' => 'Décision',   'class' => 'sikds-tag--decision'],
            ],
        ]);
    }
}
