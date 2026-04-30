<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use App\Domain\Notifications\Services\UserNotificationService;
use App\Domain\Users\Models\User;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\UserDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly UserDashboardService $userDashboard,
        private readonly UserNotificationService $notifications,
    ) {}

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->userDashboard->shouldShowSimpleDashboard($user)) {
            return view('dashboard-user', [
                'stats' => $this->userDashboard->statsForUser($user),
                'recentDocuments' => $this->userDashboard->recentDocumentsForUser($user, 5),
                'latestNotifications' => $this->notifications->latestForUser($user, 4),
                'notificationService' => $this->notifications,
                'unreadCount' => $this->notifications->unreadCountForUser($user),
            ]);
        }

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
