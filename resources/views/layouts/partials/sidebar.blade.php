@php
$safeRoute = static fn (string $name): string => \Illuminate\Support\Facades\Route::has($name) ? route($name) : '#';
$authUser = auth()->user();
$canSeeDocuments = $authUser && (
    $authUser->can('document.view.assigned')
    || $authUser->can('document.view.own_institution')
    || $authUser->can('document.view.all')
);

/*
 * Nav is sectioned: each group renders a small uppercase label followed by its
 * items. A group whose items are all hidden renders nothing (label included).
 * The sidebar is a light surface, so every item uses its blue icon variant;
 * idle items are dimmed via .sikds-nav-icon--idle (see layout.css).
 */
$groups = [
    [
        'label' => __('Principal'),
        'items' => [
            ['id' => 'dashboard', 'label' => __('Tableau de Bord'), 'icon' => '/dashboard-blue.svg', 'icon_active' => '/dashboard-blue.svg', 'href' => $safeRoute('dashboard'), 'visible' => true],
        ],
    ],
    [
        'label' => __('Documents'),
        'items' => [
            ['id' => 'documents', 'label' => __('Documents'), 'icon' => '/document-blue.svg', 'icon_active' => '/document-blue.svg', 'href' => $safeRoute('documents.index'), 'visible' => $canSeeDocuments],
            ['id' => 'tags', 'label' => __('Tags'), 'icon' => '/tags-blue.svg', 'icon_active' => '/tags-blue.svg', 'href' => $safeRoute('tags.index'), 'visible' => $authUser?->can('tag.manage')],
            ['id' => 'chatbot', 'label' => __('Chatbot'), 'icon' => '/search-blue.svg', 'icon_active' => '/search-blue.svg', 'href' => $safeRoute('rag.index'), 'visible' => $authUser?->can('rag.query') || $authUser?->can('search.basic')],
            ['id' => 'indexing', 'label' => __('Moniteur Indexation'), 'icon' => '/indexing-blue.svg', 'icon_active' => '/indexing-blue.svg', 'href' => $safeRoute('indexing.index'), 'visible' => $authUser?->can('indexing.manage')],
        ],
    ],
    [
        'label' => __('Administration'),
        'items' => [
            ['id' => 'users', 'label' => __('Utilisateurs'), 'icon' => '/users-blue.svg', 'icon_active' => '/users-blue.svg', 'href' => $safeRoute('users.index'), 'visible' => $authUser?->can('user.view.all')],
            ['id' => 'roles', 'label' => __('Rôles'), 'icon' => '/key-blue.svg', 'icon_active' => '/key-blue.svg', 'href' => $safeRoute('roles.index'), 'visible' => $authUser?->can('role.view')],
            ['id' => 'permissions', 'label' => __('Permissions'), 'icon' => '/permissions-blue.svg', 'icon_active' => '/permissions-blue.svg', 'href' => $safeRoute('permissions.index'), 'visible' => $authUser?->can('user.assign.permissions')],
            ['id' => 'institutions', 'label' => __('Institutions'), 'icon' => '/institutions-blue.svg', 'icon_active' => '/institutions-blue.svg', 'href' => $safeRoute('institutions.index'), 'visible' => $authUser?->can('institution.view')],
        ],
    ],
    [
        'label' => __('Outils'),
        'items' => [
            ['id' => 'traceability', 'label' => __('Traçabilité'), 'icon' => '/traceability-blue.svg', 'icon_active' => '/traceability-blue.svg', 'href' => $safeRoute('watermark.index'), 'visible' => $authUser?->can('audit.view')],
            ['id' => 'audits', 'label' => __("Journaux d'Audit"), 'icon' => '/audit-blue.svg', 'icon_active' => '/audit-blue.svg', 'href' => $safeRoute('audits.index'), 'visible' => $authUser?->can('audit.view')],
            ['id' => 'notifications', 'label' => __('Notifications'), 'icon' => '/bell-blue.svg', 'icon_active' => '/bell-blue.svg', 'href' => $safeRoute('notifications.index'), 'visible' => $authUser?->can('audit.view')],
            ['id' => 'settings', 'label' => __('Paramètres'), 'icon' => '/parameters-blue.svg', 'icon_active' => '/parameters-blue.svg', 'href' => $safeRoute('settings.index'), 'visible' => $authUser?->can('audit.view')],
        ],
    ],
];
@endphp

<aside class="sikds-sidebar" id="sikds-sidebar">

    <button
        type="button"
        class="sikds-sidebar-close"
        id="sikds-sidebar-close"
        aria-label="{{ __('Fermer le menu latéral') }}"
    >✕</button>

    <div class="flex h-full flex-col">

        <div class="sikds-sidebar-logo-wrap">
            <img src="/progress-blue-logo.png" alt="Progress" class="sikds-sidebar-logo sikds-sidebar-logo--progres">
        </div>

        <nav class="mt-4 flex-1 space-y-1 overflow-y-auto px-4 pb-6" aria-label="{{ __('Navigation principale') }}">
            @foreach ($groups as $group)
                @php
                    $visibleItems = array_values(array_filter($group['items'], fn (array $item): bool => (bool) ($item['visible'] ?? true)));
                @endphp
                @continue($visibleItems === [])

                <span class="sikds-nav-group-label">{{ $group['label'] }}</span>

                @foreach ($visibleItems as $item)
                    @php $isActive = ($activeNav ?? 'dashboard') === $item['id']; @endphp
                    <a
                        href="{{ $item['href'] }}"
                        data-nav-id="{{ $item['id'] }}"
                        data-icon-idle="{{ $item['icon'] }}"
                        data-icon-active="{{ $item['icon_active'] }}"
                        class="sikds-nav-link {{ $isActive ? 'sikds-nav-link--active' : 'sikds-nav-link--idle' }}"
                        @if($item['href'] === '#') aria-disabled="true" @endif
                        @if($isActive) aria-current="page" @endif
                    >
                        <img
                            src="{{ $isActive ? $item['icon_active'] : $item['icon'] }}"
                            alt=""
                            class="sikds-nav-icon h-5 w-5 shrink-0 {{ $isActive ? '' : 'sikds-nav-icon--idle' }}"
                        >
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        {{-- Language switch — mobile only (desktop has it in the top header). --}}
        <div class="lg:hidden mt-auto shrink-0 border-t border-slate-100 px-4 pb-5 pt-4">
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Langue') }}</p>
            <div class="flex gap-2">
                @foreach (config('languages.lang', []) as $code => $label)
                    <a
                        href="{{ route('changeLanguage', ['lang' => $code]) }}"
                        class="flex-1 rounded-lg px-2 py-2 text-center text-xs font-semibold transition-colors {{ $code === app()->getLocale() ? 'bg-[#1c398e] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                        @if($code === app()->getLocale()) aria-current="true" @endif
                    >{{ strtoupper($code) }}</a>
                @endforeach
            </div>
        </div>

    </div>
</aside>
