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
                ['label' => 'Directive', 'class' => 'sikds-tag--directive'],
                ['label' => 'Urgente', 'class' => 'sikds-tag--urgent'],
                ['label' => 'Décision', 'class' => 'sikds-tag--decision'],
                ['label' => 'Règlement', 'class' => 'sikds-tag--reg'],
                ['label' => 'Rapport', 'class' => 'sikds-tag--rapport'],
                ['label' => 'Budget', 'class' => 'sikds-tag--decision'],
                ['label' => 'Pédagogie', 'class' => 'sikds-tag--directive'],
            ],
        ]);
    }

    public function show(string $document): View
    {
        $doc = [
            'title'       => 'Circulaire MESRS - Réforme Pédagogique 2024',
            'reference'   => 'MESRS/DG/2024/045',
            'status'      => 'active',
            'description' => "Cette circulaire présente les nouvelles directives concernant la réforme pédagogique dans l'enseignement supérieur. Elle définit les modalités de mise en œuvre, les échéances et les responsabilités de chaque institution dans ce processus de transformation.",
            'tags'        => [
                ['label' => 'Directive',  'class' => 'sikds-tag--directive'],
                ['label' => 'Urgent',     'class' => 'sikds-tag--urgent'],
                ['label' => 'Pédagogie',  'class' => 'sikds-tag--directive'],
                ['label' => 'Réforme',    'class' => 'sikds-tag--directive'],
            ],
            'institution'    => 'MESRS',
            'issue_date'     => '15 mars 2024',
            'effective_date' => '1 avril 2024',
            'expiry_date'    => '31 mars 2025',
            'audience'       => 'Toutes les institutions',
            'views'          => 234,
            'downloads'      => 89,
            'version'        => 'v2.1',
            'file_name'      => 'circulaire-mesrs-2024-045.pdf',
            'file_type'      => 'PDF',
            'file_size'      => '2.4 MB',
            'versions'       => [
                [
                    'title'       => 'Version 2.1',
                    'status'      => 'Actuelle',
                    'status_class' => 'sikds-doc-pill--current',
                    'meta'        => '28/03/2024 • 2.4 MB',
                    'description' => "Corrections mineures sur les dates d'échéance",
                ],
                [
                    'title'       => 'Version 2.0',
                    'status'      => null,
                    'status_class' => null,
                    'meta'        => '25/03/2024 • 2.3 MB',
                    'description' => "Ajout de la section sur les modalités d'évaluation",
                ],
                [
                    'title'       => 'Version 1.0',
                    'status'      => null,
                    'status_class' => null,
                    'meta'        => '20/03/2024 • 2.1 MB',
                    'description' => 'Version initiale publiée',
                ],
            ],
            'download_history' => [
                [
                    'title'    => 'Téléchargement #1',
                    'meta'     => 'Prof. Bennani Sara • s.bennani@uh2c.ac.dz • 28/03/2024',
                    'uuid'     => 'WM-2024-7F8A9B3C',
                ],
                [
                    'title'    => 'Téléchargement #2',
                    'meta'     => 'Dr. Mansouri Laila • l.mansouri@enp.ac.dz • 28/03/2024',
                    'uuid'     => 'WM-2024-5D3E2F1A',
                ],
                [
                    'title'    => 'Téléchargement #3',
                    'meta'     => 'Pr M. Chakri Mohamed • m.chakri@um5.ac.dz • 27/03/2024',
                    'uuid'     => 'WM-2024-9B4C6E8D',
                ],
            ],
            'activities'     => [
                [
                    'title'      => 'Document téléchargé',
                    'meta'       => 'Prof. Bennani Sara • s.bennani@uh2c.ac.dz',
                    'timestamp'  => '2024-03-28 15:30',
                    'icon'       => 'fa-solid fa-download',
                    'icon_class' => 'sikds-doc-event-icon--download',
                ],
                [
                    'title'      => 'Version 2.1 publiée',
                    'meta'       => 'Équipe documentaire • docs@mesrs.dz',
                    'timestamp'  => '2024-03-28 10:00',
                    'icon'       => 'fa-regular fa-file-lines',
                    'icon_class' => 'sikds-doc-event-icon--version',
                ],
                [
                    'title'      => 'Document partagé avec Universités',
                    'meta'       => 'Admin MESRS • admin@mesrs.dz',
                    'timestamp'  => '2024-03-28 09:45',
                    'icon'       => 'fa-solid fa-share-nodes',
                    'icon_class' => 'sikds-doc-event-icon--share',
                ],
            ],
        ];

        return view('documents.show', [
            'activeNav' => 'documents',
            'document'  => $doc,
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
