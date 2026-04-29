@extends('layouts.app')
@section('page_title', 'Tableau de Bord')
@section('page_subtitle', "Aperçu de l'activité du système SIKDS")
@section('content')
    @php
        $activeNav = 'dashboard';
        $authUser = auth()->user();
        $userName = $authUser?->full_name ?? $authUser?->name ?? $authUser?->email ?? 'Utilisateur';
        $canChat = $authUser?->can('rag.query') ?? false;
        $canSeeDocuments = $authUser && (
            $authUser->can('document.view.assigned')
            || $authUser->can('document.view.own_institution')
            || $authUser->can('document.view.all')
        );
        /** @var \App\Domain\Notifications\Services\UserNotificationService $notificationService */
        $notificationService = $notificationService;
    @endphp

    {{-- Welcome Section --}}
    <section class="rounded-2xl bg-white border shadow-sm px-6 py-6 mb-5"
             style="border-color:rgba(0,0,0,.08);">
        <h2 class="text-2xl sm:text-3xl font-bold" style="color:var(--sikds-primary);">
            Bonjour, {{ $userName }}
        </h2>
        <p class="mt-1 text-sm" style="color:var(--sikds-muted)">
            Accédez rapidement à vos documents et recherches
        </p>
    </section>

    {{-- Stats Cards --}}
    <section class="grid gap-5 md:grid-cols-2 mb-5">
        @foreach ($stats as $stat)
            <article class="rounded-2xl bg-white border shadow-sm px-6 py-5"
                     style="border-color:rgba(0,0,0,.08);">
                <img src="{{ $stat['icon'] }}" alt="" class="h-8 w-8 mb-4">
                <p class="text-3xl font-bold" style="color:var(--sikds-ink);">{{ $stat['value'] }}</p>
                <p class="mt-1 text-sm" style="color:var(--sikds-muted);">{{ $stat['label'] }}</p>
            </article>
        @endforeach
    </section>

    {{-- Recent Documents + Recent Chats --}}
    <section class="grid gap-5 md:grid-cols-2 mb-5 items-stretch">
        <article class="rounded-2xl bg-white border shadow-sm overflow-hidden flex flex-col"
                 style="border-color:rgba(0,0,0,.08);">
            <div class="px-6 py-5 border-b" style="border-color:rgba(0,0,0,.06);">
                <h3 class="text-lg font-bold" style="color:var(--sikds-ink);">Documents Récents</h3>
                <p class="text-xs mt-0.5" style="color:var(--sikds-muted);">Vos derniers documents consultés</p>
            </div>
            <div class="flex-1 divide-y" style="border-color:rgba(0,0,0,.06);">
                @forelse ($recentDocuments as $doc)
                    <a
                        href="{{ route('documents.index') }}"
                        class="flex items-center justify-between px-6 py-3 hover:bg-slate-50 transition-colors"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate" style="color:var(--sikds-ink);">{{ $doc['title'] }}</p>
                            @if ($doc['reference'])
                                <p class="text-xs" style="color:var(--sikds-muted);">{{ $doc['reference'] }}</p>
                            @endif
                        </div>
                        <span class="text-xs shrink-0 ml-3" style="color:var(--sikds-muted);">{{ $doc['time'] }}</span>
                    </a>
                @empty
                    <div class="px-6 py-10 text-center">
                        <i class="fa-regular fa-folder-open text-2xl" style="color:var(--sikds-muted);"></i>
                        <p class="mt-2 text-sm" style="color:var(--sikds-muted);">Aucun document récent</p>
                    </div>
                @endforelse
            </div>
            @if ($canSeeDocuments)
                <div class="mt-auto px-6 py-3 border-t bg-slate-50/50" style="border-color:rgba(0,0,0,.06);">
                    <a href="{{ route('documents.index') }}" class="text-xs font-semibold hover:underline" style="color:var(--sikds-primary);">
                        Voir tous les documents →
                    </a>
                </div>
            @endif
        </article>

        <article class="rounded-2xl bg-white border shadow-sm overflow-hidden flex flex-col"
                 style="border-color:rgba(0,0,0,.08);"
                 x-data="sikdsRecentChats({ userId: @js($authUser?->id ?? 0) })"
                 x-init="load()">
            <div class="px-6 py-5 border-b" style="border-color:rgba(0,0,0,.06);">
                <h3 class="text-lg font-bold" style="color:var(--sikds-ink);">Chats Récents</h3>
                <p class="text-xs mt-0.5" style="color:var(--sikds-muted);">Vos derniers chats</p>
            </div>
            <div class="flex-1 divide-y" style="border-color:rgba(0,0,0,.06);">
                <template x-if="items.length > 0">
                    <div>
                        <template x-for="(chat, i) in items" :key="i">
                            <a :href="@js(route('rag.index'))"
                               class="flex items-start justify-between gap-3 px-6 py-3 hover:bg-slate-50 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate" style="color:var(--sikds-ink);" x-text="chat.text"></p>
                                </div>
                                <span class="text-xs shrink-0" style="color:var(--sikds-muted);" x-text="chat.time"></span>
                            </a>
                        </template>
                    </div>
                </template>
                <template x-if="items.length === 0">
                    <div class="px-6 py-10 text-center">
                        <i class="fa-regular fa-comments text-2xl" style="color:var(--sikds-muted);"></i>
                        <p class="mt-2 text-sm" style="color:var(--sikds-muted);">Aucune activité récente</p>
                    </div>
                </template>
            </div>
            @if ($canChat)
                <div class="mt-auto px-6 py-3 border-t bg-slate-50/50" style="border-color:rgba(0,0,0,.06);">
                    <a href="{{ route('rag.index') }}" class="text-xs font-semibold hover:underline" style="color:var(--sikds-primary);">
                        Ouvrir le chatbot →
                    </a>
                </div>
            @endif
        </article>
    </section>

    {{-- Notification preview --}}
    <section class="mb-5">
        <article class="rounded-2xl bg-white border shadow-sm overflow-hidden"
                 style="border-color:rgba(0,0,0,.08);">
            <div class="flex items-center justify-between px-6 py-5 border-b" style="border-color:rgba(0,0,0,.06);">
                <div>
                    <h3 class="text-lg font-bold" style="color:var(--sikds-ink);">Notifications récentes</h3>
                    <p class="text-xs mt-0.5" style="color:var(--sikds-muted);">
                        @if ($unreadCount > 0)
                            {{ $unreadCount }} notification{{ $unreadCount > 1 ? 's' : '' }} non lue{{ $unreadCount > 1 ? 's' : '' }}
                        @else
                            Vous êtes à jour
                        @endif
                    </p>
                </div>
                <a href="{{ route('notifications.inbox') }}" class="text-xs font-semibold hover:underline" style="color:var(--sikds-primary);">
                    Voir toutes
                </a>
            </div>
            <div class="divide-y" style="border-color:rgba(0,0,0,.06);">
                @forelse ($latestNotifications as $notification)
                    @php
                        $isRead = $notification->read_at !== null;
                        $message = $notificationService->buildMessage($notification);
                        $hasDocument = $notification->document_id !== null;
                    @endphp
                    @if ($hasDocument)
                        <form method="POST" action="{{ route('notifications.read', ['notification' => $notification->id]) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-start gap-3 px-6 py-3 text-left hover:bg-slate-50 transition-colors {{ $isRead ? '' : 'bg-blue-50/30' }}">
                                <span class="mt-1.5 inline-block h-2 w-2 rounded-full shrink-0 {{ $isRead ? 'bg-transparent' : 'bg-[#1E3A8A]' }}"
                                      aria-hidden="true"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm {{ $isRead ? 'text-slate-700' : 'font-semibold text-slate-900' }}">{{ $message }}</p>
                                    <p class="text-xs mt-1" style="color:var(--sikds-muted);">{{ $notification->created_at?->locale('fr')->diffForHumans() }}</p>
                                </div>
                            </button>
                        </form>
                    @else
                        <div class="flex items-start gap-3 px-6 py-3 {{ $isRead ? '' : 'bg-blue-50/30' }}">
                            <span class="mt-1.5 inline-block h-2 w-2 rounded-full shrink-0 {{ $isRead ? 'bg-transparent' : 'bg-[#1E3A8A]' }}"
                                  aria-hidden="true"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm {{ $isRead ? 'text-slate-700' : 'font-semibold text-slate-900' }}">{{ $message }}</p>
                                <p class="text-xs mt-1" style="color:var(--sikds-muted);">{{ $notification->created_at?->locale('fr')->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="px-6 py-10 text-center">
                        <i class="fa-regular fa-bell-slash text-2xl" style="color:var(--sikds-muted);"></i>
                        <p class="mt-2 text-sm" style="color:var(--sikds-muted);">Aucune notification</p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>
@endsection
