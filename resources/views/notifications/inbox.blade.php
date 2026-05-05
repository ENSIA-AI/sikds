@extends('layouts.app')
@section('page_title', 'Mes notifications')
@section('page_subtitle', 'Documents publiés, mis à jour ou partagés qui vous concernent')
@section('content')
    @php
        /** @var \App\Domain\Notifications\Services\UserNotificationService $service */
        $service = $service;
    @endphp

    @if (session('status'))
        <div class="mb-4 rounded-[12px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white rounded-[14px] border overflow-hidden mb-5"
         style="border-color:rgba(0,0,0,.1);box-shadow:var(--sikds-shadow-panel);">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b" style="border-color:rgba(0,0,0,.08);">
            <div>
                <p class="text-sm font-semibold" style="color:var(--sikds-ink)">Boîte de réception</p>
                <p class="text-xs" style="color:var(--sikds-muted)">
                    @if ($unreadCount > 0)
                        {{ $unreadCount }} notification{{ $unreadCount > 1 ? 's' : '' }} non lue{{ $unreadCount > 1 ? 's' : '' }}
                    @else
                        Toutes vos notifications sont à jour.
                    @endif
                </p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 text-sm font-semibold border rounded-[10px] hover:bg-gray-50 transition-colors"
                            style="border-color:rgba(0,0,0,.1);color:var(--sikds-primary)">
                        Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>

        <ul class="divide-y" style="border-color:rgba(0,0,0,.06);">
            @forelse ($items as $notification)
                @php
                    $message = $service->buildMessage($notification);
                    $isRead = $notification->read_at !== null;
                    $typeLabel = $service->shortLabel($notification->type);
                    $hasDocument = $notification->document_id !== null;
                @endphp
                <li class="px-5 py-4 hover:bg-slate-50 transition-colors {{ $isRead ? '' : 'bg-blue-50/30' }}">
                    <form method="POST"
                          action="{{ route('notifications.read', ['notification' => $notification->id]) }}"
                          class="flex items-start gap-3">
                        @csrf
                        <span class="mt-1 inline-block h-2 w-2 rounded-full shrink-0 {{ $isRead ? 'bg-transparent' : 'bg-[#1E3A8A]' }}"
                              aria-hidden="true"></span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[11px] uppercase tracking-wider font-semibold rounded-full px-2 py-0.5"
                                      style="background:rgba(30,58,138,.08);color:#1E3A8A;">
                                    {{ $typeLabel }}
                                </span>
                                <span class="text-xs" style="color:var(--sikds-muted)">
                                    {{ $notification->created_at?->locale('fr')->isoFormat('D MMMM YYYY, HH:mm') }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm {{ $isRead ? 'font-normal text-slate-700' : 'font-semibold text-slate-900' }}">
                                {{ $message }}
                            </p>
                            @if ($notification->document?->reference_number)
                                <p class="text-xs mt-1" style="color:var(--sikds-muted)">
                                    Référence : {{ $notification->document->reference_number }}
                                </p>
                            @endif
                        </div>
                        <div class="shrink-0">
                            @if ($hasDocument)
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-[10px] text-white"
                                        style="background-color:var(--sikds-primary);">
                                    Ouvrir
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </button>
                            @else
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-[10px] border hover:bg-slate-50"
                                        style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">
                                    Marquer comme lu
                                </button>
                            @endif
                        </div>
                    </form>
                </li>
            @empty
                <li class="px-5 py-14 text-center">
                    <i class="fa-regular fa-bell-slash text-3xl" style="color:var(--sikds-muted)"></i>
                    <p class="mt-3 text-sm" style="color:var(--sikds-muted)">Aucune notification</p>
                </li>
            @endforelse
        </ul>

        @if ($items->hasPages())
            <div class="px-5 py-4 border-t flex items-center justify-between" style="border-color:rgba(0,0,0,.1);">
                <p class="text-sm" style="color:var(--sikds-muted)">
                    Affichage de {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} sur {{ number_format($items->total()) }}
                </p>
                <div class="flex items-center gap-2">
                    @if ($items->onFirstPage())
                        <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed"
                              style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Précédent</span>
                    @else
                        <a href="{{ $items->previousPageUrl() }}"
                           class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors"
                           style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Précédent</a>
                    @endif
                    @if ($items->hasMorePages())
                        <a href="{{ $items->nextPageUrl() }}"
                           class="px-4 py-1.5 text-sm border rounded-[10px] hover:bg-gray-50 transition-colors"
                           style="border-color:rgba(0,0,0,.1);color:var(--sikds-ink)">Suivant</a>
                    @else
                        <span class="px-4 py-1.5 text-sm border rounded-[10px] opacity-40 cursor-not-allowed"
                              style="border-color:rgba(0,0,0,.1);color:var(--sikds-muted)">Suivant</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
