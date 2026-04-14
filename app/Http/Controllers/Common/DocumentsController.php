<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use Illuminate\View\View;

class DocumentsController
{
    public function create(): View
    {
        return view('documents.upload', [
            'activeNav' => 'documents',
            'availableTags' => [
                'Directive',
                'Urgente',
                'Décision',
                'Règlement',
                'Rapport',
                'Budget',
                'Pédagogie',
            ],
        ]);
    }

    public function index(): View
    {
        return view('documents.index', [
            'activeNav' => 'documents',
            'documents' => [
                [
                    'title'     => 'Directive MESRS – Réforme Pédagogique 2024',
                    'reference' => 'MESRS/DG/2024/045',
                    'status'    => 'active',
                    'tags'      => [
                        ['label' => 'Directive', 'class' => 'sikds-tag--directive'],
                        ['label' => 'Urgente',   'class' => 'sikds-tag--urgent'],
                    ],
                    'extra_tags'      => 1,
                    'target_audience' => 'Toutes les institutions',
                    'issue_date'      => '15/03/2024',
                    'actions'         => ['view', 'edit', 'download', 'copy', 'delete'],
                ],
                [
                    'title'     => 'Décision Ministérielle sur le Budget Universitaire',
                    'reference' => 'DR/2024/012',
                    'status'    => 'active',
                    'tags'      => [
                        ['label' => 'Décision', 'class' => 'sikds-tag--decision'],
                        ['label' => 'Budget',   'class' => 'sikds-tag--decision'],
                    ],
                    'extra_tags'      => 1,
                    'target_audience' => 'Universités',
                    'issue_date'      => '10/03/2024',
                    'actions'         => ['view', 'edit', 'download', 'copy', 'delete'],
                ],
                [
                    'title'     => "Rapport Annuel d'Activité 2023",
                    'reference' => 'RAA/2023/001',
                    'status'    => 'draft',
                    'tags'      => [
                        ['label' => 'Rapport', 'class' => 'sikds-tag--rapport'],
                        ['label' => 'Annuel',  'class' => 'sikds-tag--rapport'],
                    ],
                    'extra_tags'      => 0,
                    'target_audience' => 'Cabinet du Ministre',
                    'issue_date'      => '01/03/2024',
                    'actions'         => ['view', 'edit', 'download', 'copy', 'delete'],
                ],
                [
                    'title'     => 'Directive Administrative Obsolète',
                    'reference' => 'CA/2023/145',
                    'status'    => 'archived',
                    'tags'      => [
                        ['label' => 'Directive',     'class' => 'sikds-tag--directive'],
                        ['label' => 'Administratif', 'class' => 'sikds-tag--directive'],
                    ],
                    'extra_tags'      => 0,
                    'target_audience' => 'Toutes les institutions',
                    'issue_date'      => '15/01/2023',
                    'actions'         => ['view', 'edit', 'download', 'copy', 'delete'],
                ],
                [
                    'title'     => "Règlement Cadre sur l'Enseignement Supérieur",
                    'reference' => 'LC/2024/003',
                    'status'    => 'active',
                    'tags'      => [
                        ['label' => 'Règlement', 'class' => 'sikds-tag--reg'],
                        ['label' => 'Réforme',   'class' => 'sikds-tag--reg'],
                    ],
                    'extra_tags'      => 0,
                    'target_audience' => 'Toutes les institutions',
                    'issue_date'      => '20/02/2024',
                    'actions'         => ['view', 'edit', 'download', 'copy', 'delete'],
                ],
                [
                    'title'     => 'Document Test Supprimé',
                    'reference' => 'TEST/2024/001',
                    'status'    => 'deleted',
                    'tags'      => [
                        ['label' => 'Test', 'class' => 'sikds-tag--decision'],
                    ],
                    'extra_tags'      => 0,
                    'target_audience' => 'Test',
                    'issue_date'      => '01/01/2024',
                    'actions'         => ['view', 'restore'],
                ],
            ],
        ]);
    }
}
