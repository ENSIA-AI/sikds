@php
$items = [
    ['id' => 'dashboard',    'label' => 'Tableau de Bord',    'icon' => '/dashboard-blue.svg',   'icon_active' => '/dashboard-blue.svg',    'href' => route('dashboard')],
    ['id' => 'documents',    'label' => 'Documents',           'icon' => '/document.svg',          'icon_active' => '/document-blue.svg',     'href' => route('documents.index')],
    ['id' => 'tags',         'label' => 'Tags',                'icon' => '/tags.svg',              'icon_active' => '/tags-blue.svg',         'href' => '#'],
    ['id' => 'distribution', 'label' => 'Distribution',        'icon' => '/distribution.svg',      'icon_active' => '/distribution-blue.svg', 'href' => '#'],
    ['id' => 'users',        'label' => 'Utilisateurs',        'icon' => '/people.svg',            'icon_active' => '/users-blue.svg',        'href' => '#'],
    ['id' => 'roles',        'label' => 'Rôles',               'icon' => '/key.svg',               'icon_active' => '/key-blue.svg',          'href' => '#'],
    ['id' => 'permissions',  'label' => 'Permissions',         'icon' => '/permissions-blue.svg',  'icon_active' => '/permissions-blue.svg',  'href' => '#'],
    ['id' => 'institutions', 'label' => 'Institutions',        'icon' => '/building.svg',          'icon_active' => '/institutions-blue.svg', 'href' => '#'],
    ['id' => 'chatbot',      'label' => 'Chatbot',             'icon' => '/search.svg',            'icon_active' => '/search-blue.svg',       'href' => route('rag.index')],
    ['id' => 'indexing',     'label' => 'Moniteur Indexation', 'icon' => '/indexing.svg',          'icon_active' => '/indexing-blue.svg',     'href' => route('indexing.index')],
    ['id' => 'traceability', 'label' => 'Traçabilité',         'icon' => '/traceability.svg',      'icon_active' => '/traceability-blue.svg', 'href' => '#'],
    ['id' => 'audits',       'label' => "Journaux d'Audit",    'icon' => '/audit.svg',             'icon_active' => '/audit-blue.svg',        'href' => '#'],
    ['id' => 'notifications','label' => 'Notifications',       'icon' => '/bell.svg',              'icon_active' => '/bell-blue.svg',         'href' => '#'],
    ['id' => 'settings',     'label' => 'Paramètres',          'icon' => '/parameters.svg',        'icon_active' => '/parameters-blue.svg',   'href' => '#'],
];
@endphp

<aside class="sikds-sidebar" id="sikds-sidebar">

    <button
        type="button"
        class="sikds-sidebar-close"
        id="sikds-sidebar-close"
        aria-label="Fermer le menu latéral"
    >✕</button>

    <div class="flex h-full flex-col">

        <div class="sikds-sidebar-logo-wrap">
            <img src="/favicon.svg" alt="Progres" class="sikds-sidebar-logo sikds-sidebar-logo--progres">
        </div>

        <nav class="mt-6 flex-1 space-y-1 overflow-y-auto px-4 pb-6" aria-label="Navigation principale">
            @foreach ($items as $item)
                @php $isActive = ($activeNav ?? 'dashboard') === $item['id']; @endphp
                <a
                    href="{{ $item['href'] }}"
                    data-nav-id="{{ $item['id'] }}"
                    data-icon-idle="{{ $item['icon'] }}"
                    data-icon-active="{{ $item['icon_active'] }}"
                    class="sikds-nav-link {{ $isActive ? 'sikds-nav-link--active' : 'sikds-nav-link--idle' }}"
                    @if($item['href'] === '#') aria-disabled="true" @endif
                >
                    <img
                        src="{{ $isActive ? $item['icon_active'] : $item['icon'] }}"
                        alt=""
                        class="sikds-nav-icon h-5 w-5 shrink-0 {{ $isActive ? '' : 'sikds-nav-icon--idle' }}"
                    >
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

    </div>
</aside>
