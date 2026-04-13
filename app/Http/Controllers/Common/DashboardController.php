<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use Illuminate\View\View;

class DashboardController
{
    public function index(): View
    {
        return view('dashboard', [
            'kpis' => [
                ['icon' => '/document.svg', 'value' => '2,847', 'label' => 'Total Documents', 'trend' => '+12%'],
                ['icon' => '/upload-blue.svg', 'value' => '156', 'label' => 'Téléversements Récents', 'trend' => '+8%'],
                ['icon' => '/people.svg', 'value' => '342', 'label' => 'Utilisateurs', 'trend' => '+5%'],
                ['icon' => '/building-blue.svg', 'value' => '24', 'label' => 'Institutions', 'trend' => '+2'],
            ],
            'activities' => [
                [
                    'icon' => '/upload.svg',
                    'user' => 'Dr. Ahmadi Karim',
                    'action' => 'a téléversé',
                    'document' => '"circulaire MESRS-2024-045"',
                    'time' => 'Il y a 5 minutes',
                ],
                [
                    'icon' => '/download-black.svg',
                    'user' => 'Mme. Benali Fatima',
                    'action' => 'a téléchargé',
                    'document' => '"Décision Royal 2024-012"',
                    'time' => 'Il y a 12 minutes',
                ],
                [
                    'icon' => '/edit-black.svg',
                    'user' => 'M. Chakri Mohamed',
                    'action' => 'a modifié',
                    'document' => '"Rapport Annuel 2023"',
                    'time' => 'Il y a 1 heure',
                ],
                [
                    'icon' => '/archive.svg',
                    'user' => 'Prof. Idrissi Hassan',
                    'action' => 'a archivé',
                    'document' => '"Circulaire Obsolète 2020"',
                    'time' => 'Il y a 2 heures',
                ],
            ],
            'alerts' => [
                ['type' => 'danger', 'message' => "Échec d'indexation pour 3 documents", 'timestamp' => 'Il y a 30 minutes'],
                ['type' => 'warning', 'message' => '15 documents expirent dans les 7 prochains jours', 'timestamp' => 'Il y a 2 heures'],
                ['type' => 'info', 'message' => 'Maintenance système programmée pour ce soir 23h', 'timestamp' => 'Il y a 4 heures'],
            ],
            'statusStats' => [
                ['label' => 'Brouillon', 'value' => '127'],
                ['label' => 'Actif', 'value' => '892'],
                ['label' => 'Archivé', 'value' => '228'],
            ],
            'popularTags' => [
                ['label' => 'Directive', 'class' => 'sikds-tag--directive'],
                ['label' => 'Urgent', 'class' => 'sikds-tag--urgent'],
                ['label' => 'Régulation', 'class' => 'sikds-tag--reg'],
                ['label' => 'Rapport', 'class' => 'sikds-tag--rapport'],
                ['label' => 'Décision', 'class' => 'sikds-tag--decision'],
            ],
            'activeInstitutions' => [
                ['label' => 'MESRS', 'value' => '45 docs'],
                ['label' => 'UH2C', 'value' => '32 docs'],
                ['label' => 'IGF', 'value' => '28 docs'],
            ],
        ]);
    }
}
