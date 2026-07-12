@extends('layouts.app')
@php
    $activeNav = 'tags';
@endphp
@section('page_title', __('Gestion des Tags'))
@section('page_subtitle', __('Organiser et catégoriser les documents'))

@push('scripts')
    @vite(['resources/js/pages/tags.js'])
@endpush

@section('content')

{{-- Page config consumed by resources/js/pages/tags.js (JSON data, never executed). --}}
@php
    $tagsPageConfig = [
        'i18n' => [
            'editTagTitle' => __('Modifier le Tag'),
            'createTagTitle' => __('Créer un Tag'),
            'editTagSubtitle' => __('Modifier les informations du tag'),
            'createTagSubtitle' => __('Ajouter un nouveau tag au système'),
            'documents' => __('documents'),
            'document' => __('document'),
            'save' => __('Enregistrer'),
            'createTag' => __('Créer le Tag'),
            'nameRequired' => __('Le nom est requis.'),
            'similarTagExists' => __('Un tag similaire existe déjà : « :name ».'),
            'colorAlreadyUsed' => __('Cette couleur est déjà utilisée par « :name ».'),
            'newCategoryRequired' => __('Saisis un nom pour la nouvelle catégorie.'),
            'categoryAlreadyExists' => __('Cette catégorie existe déjà.'),
            'theseDocuments' => __('ces documents'),
            'thisDocument' => __('ce document'),
        ],
        'existingTags' => $allTags,
        'existingCategories' => $existingCategories,
        'categoryLabels' => $categoryLabels,
        'oldForm' => ($errors->has('name') || $errors->has('color') || $errors->has('category') || $errors->has('new_category')) ? [
            'name' => old('name', ''),
            'color' => old('color', '#e0e7ff'),
            'category' => old('category_mode') === 'new' ? '__new__' : old('category', ''),
            'newCategory' => old('new_category', ''),
            'errorName' => $errors->first('name') ?? '',
            'errorColor' => $errors->first('color') ?? '',
            'errorCategory' => $errors->first('new_category') ?? ($errors->first('category') ?? ''),
        ] : null,
    ];
@endphp
<script type="application/json" id="tags-page-config">@json($tagsPageConfig)</script>

<div x-data="tagsPage()"
     x-init="init()"
     @keydown.escape.window="closeModal(); closeDeleteModal()"
     @mousemove.window="onWindowMouseMove($event)"
     @mouseup.window="stopDrag()"
     @touchmove.window.prevent="onWindowTouchMove($event)"
     @touchend.window="stopDrag()">

    @if (session('success'))
        <x-sikds-banner type="success" :message="session('success')" />
    @endif

    @if (session('error'))
        <x-sikds-banner type="danger" :message="session('error')" />
    @endif

    @if ($errors->any())
        <x-sikds-banner type="danger" :message="$errors->first()" />
    @endif

    {{-- Toolbar --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
        <div class="min-w-0">
            <h2 class="text-2xl sm:text-[28px] font-semibold sikds-ink leading-tight">{{ __('Tags des Documents') }}</h2>
            <p class="mt-1 text-sm sikds-muted-text">{{ __('Organiser et catégoriser les documents') }}</p>
        </div>
        <button type="button" @click="openModal()" class="sikds-tags-btn-create shrink-0 self-start sm:self-auto">
            <i class="fa-solid fa-plus text-sm"></i>
            <span>{{ __('Créer un Tag') }}</span>
        </button>
    </div>

    {{-- Panels grid --}}
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">

        {{-- Predefined groups --}}
        @foreach ($predefinedGroups as $category => $tags)
            <div class="sikds-tag-panel">
                <h3 class="sikds-tag-panel-title">{{ $categoryLabels[$category] ?? ucfirst($category) }}</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($tags as $tag)
                        <div class="sikds-tag-row group">
                            <div class="flex items-center gap-3">
                                <div class="sikds-tag-badge" style="--badge: {{ $tag->color ?? '#e0e7ff' }}; background-color: var(--badge); color: color-mix(in srgb, var(--badge), black 55%)">
                                    <svg class="sikds-tag-svg-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M10.4887 2.155C10.1762 1.8424 9.7523 1.6667 9.3103 1.6666H3.3337C2.8916 1.6666 2.4677 1.8422 2.1552 2.1548C1.8426 2.4673 1.667 2.8913 1.667 3.3333V9.31C1.6671 9.7519 1.8427 10.1758 2.1553 10.4883L9.4087 17.7416C9.7874 18.118 10.2997 18.3292 10.8337 18.3292C11.3676 18.3292 11.8799 18.118 12.2587 17.7416L17.742 12.2583C18.1184 11.8795 18.3296 11.3673 18.3296 10.8333C18.3296 10.2993 18.1184 9.787 17.742 9.4083L10.4887 2.155Z" stroke="currentColor" stroke-width="1.6667" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M6.2497 6.6667C6.4798 6.6667 6.6663 6.4802 6.6663 6.25C6.6663 6.0199 6.4798 5.8334 6.2497 5.8334C6.0196 5.8334 5.833 6.0199 5.833 6.25C5.833 6.4802 6.0196 6.6667 6.2497 6.6667Z" fill="currentColor" stroke="currentColor" stroke-width="1.6667" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium sikds-ink">{{ $tag->name }}</p>
                                    <p class="text-xs sikds-muted-text">{{ trans_choice('{1} :count document|[2,*] :count documents', $tag->documents_count, ['count' => $tag->documents_count]) }}</p>
                                </div>
                            </div>
                            <div class="sikds-tag-actions">
                                <button type="button" class="sikds-tag-action-btn" title="{{ __('Modifier') }}"
                                    @click="openEditModal({{ $tag->id }}, @js($tag->name), @js($tag->color ?? '#e0e7ff'), @js($tag->category), {{ $tag->documents_count }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="sikds-tag-action-btn sikds-tag-action-btn--danger" title="{{ __('Supprimer') }}"
                                    @click="openDeleteModal({{ $tag->id }}, @js($tag->name), @js($tag->color ?? '#e0e7ff'), {{ $tag->documents_count }})">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    @if ($tags->isEmpty())
                        <p class="text-sm sikds-muted-text py-2">{{ __('Aucun tag dans cette catégorie.') }}</p>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Custom tags panel --}}
        <div class="sikds-tag-panel">
            <h3 class="sikds-tag-panel-title">{{ __('Tags Personnalisés') }}</h3>
            <div class="mt-4 space-y-3">
                @forelse ($customTags as $tag)
                    <div class="sikds-tag-row group">
                        <div class="flex items-center gap-3">
                            <div class="sikds-tag-badge" style="--badge: {{ $tag->color ?? '#e0e7ff' }}; background-color: var(--badge); color: color-mix(in srgb, var(--badge), black 55%)">
                                <svg class="sikds-tag-svg-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M10.4887 2.155C10.1762 1.8424 9.7523 1.6667 9.3103 1.6666H3.3337C2.8916 1.6666 2.4677 1.8422 2.1552 2.1548C1.8426 2.4673 1.667 2.8913 1.667 3.3333V9.31C1.6671 9.7519 1.8427 10.1758 2.1553 10.4883L9.4087 17.7416C9.7874 18.118 10.2997 18.3292 10.8337 18.3292C11.3676 18.3292 11.8799 18.118 12.2587 17.7416L17.742 12.2583C18.1184 11.8795 18.3296 11.3673 18.3296 10.8333C18.3296 10.2993 18.1184 9.787 17.742 9.4083L10.4887 2.155Z" stroke="currentColor" stroke-width="1.6667" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6.2497 6.6667C6.4798 6.6667 6.6663 6.4802 6.6663 6.25C6.6663 6.0199 6.4798 5.8334 6.2497 5.8334C6.0196 5.8334 5.833 6.0199 5.833 6.25C5.833 6.4802 6.0196 6.6667 6.2497 6.6667Z" fill="currentColor" stroke="currentColor" stroke-width="1.6667" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium sikds-ink">{{ $tag->name }}</p>
                                <p class="text-xs sikds-muted-text">{{ trans_choice('{1} :count document|[2,*] :count documents', $tag->documents_count, ['count' => $tag->documents_count]) }}</p>
                            </div>
                        </div>
                        <div class="sikds-tag-actions">
                            <button type="button" class="sikds-tag-action-btn" title="{{ __('Modifier') }}"
                                @click="openEditModal({{ $tag->id }}, @js($tag->name), @js($tag->color ?? '#e0e7ff'), @js($tag->category), {{ $tag->documents_count }})">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="sikds-tag-action-btn sikds-tag-action-btn--danger" title="{{ __('Supprimer') }}"
                                @click="openDeleteModal({{ $tag->id }}, @js($tag->name), @js($tag->color ?? '#e0e7ff'), {{ $tag->documents_count }})">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm sikds-muted-text py-2">{{ __('Aucun tag personnalisé.') }}</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Create / Edit modal (portalled to <body> so the backdrop covers the full viewport, chrome included) --}}
    <template x-teleport="body">
    <div x-show="modalOpen"
         x-cloak
         class="sikds-modal-backdrop"
         @click.self="closeModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="sikds-modal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-1">

            <div class="sikds-modal-header">
                <div>
                    <h3 class="sikds-modal-title" x-text="editId ? i18n.editTagTitle : i18n.createTagTitle"></h3>
                    <p class="sikds-modal-subtitle"
                       x-text="editId ? i18n.editTagSubtitle : i18n.createTagSubtitle"></p>
                </div>
                <button type="button" @click="closeModal()" class="sikds-modal-close" aria-label="{{ __('Fermer') }}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Single unified form for create and edit --}}
            <form x-ref="tagForm"
                  method="POST"
                  :action="editId ? '/tags/' + editId : '{{ route('tags.store') }}'"
                  @submit.prevent="handleSubmit($refs.tagForm)"
                  class="sikds-modal-body">
                @csrf
                <input type="hidden" name="_method" :value="editId ? 'PATCH' : 'POST'">

                {{-- Name field --}}
                <div class="sikds-form-group">
                    <label class="sikds-form-label">
                        {{ __('Nom du tag') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           :value="form.name"
                           @input="form.name = $event.target.value; validateName()"
                           :class="errors.name ? 'sikds-form-input is-error' : 'sikds-form-input'"
                           placeholder="{{ __('Ex : Urgent, Rapport...') }}"
                           autocomplete="off"
                           required>
                    <div x-show="errors.name" x-cloak class="sikds-form-error-pill">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span x-text="errors.name"></span>
                    </div>
                </div>

                {{-- Category field --}}
                <div class="sikds-form-group">
                    <label class="sikds-form-label">{{ __('Catégorie') }}</label>
                    <div :class="errors.category ? 'sikds-select-wrap is-error' : 'sikds-select-wrap'"
                         :data-empty="form.category === '' ? 'true' : 'false'">
                        <select x-model="form.category"
                                @change="onCategoryChange()"
                                class="sikds-form-select-native">
                            <option value="">{{ __('Sélectionner une catégorie') }}</option>
                            <template x-for="c in existingCategories" :key="c">
                                <option :value="c" x-text="categoryLabels[c] || c"></option>
                            </template>
                            <option value="__new__">{{ __('+ Nouvelle catégorie…') }}</option>
                        </select>
                    </div>

                    <div x-show="form.category === '__new__'" x-cloak class="sikds-new-category">
                        <input type="text"
                               x-model="form.newCategory"
                               @input="validateCategory()"
                               :class="errors.category ? 'sikds-form-input is-error' : 'sikds-form-input'"
                               placeholder="{{ __('Nom de la nouvelle catégorie') }}"
                               maxlength="50"
                               autocomplete="off">
                    </div>

                    <div x-show="errors.category" x-cloak class="sikds-form-error-pill">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span x-text="errors.category"></span>
                    </div>

                    {{-- Hidden fields sent to server --}}
                    <input type="hidden" name="category_mode"
                           :value="form.category === '__new__' ? 'new' : (form.category ? 'existing' : 'none')">
                    <input type="hidden" name="category"
                           :value="form.category === '__new__' ? '' : form.category">
                    <input type="hidden" name="new_category" :value="form.newCategory">
                </div>

                {{-- Color picker --}}
                <div class="sikds-form-group" style="margin-bottom:0;margin-top:2px">
                    <label class="sikds-form-label">{{ __('Couleur du badge') }}</label>

                    <div class="sikds-color-picker">
                        {{-- Spectrum square --}}
                        <canvas x-ref="spectrumCanvas"
                                width="280" height="120"
                                class="sikds-cp-spectrum"
                                @mousedown.prevent="startSpectrumDrag($event)"
                                @touchstart.prevent="startSpectrumTouch($event)">
                        </canvas>

                        {{-- Hue slider --}}
                        <input type="range"
                               min="0" max="360" step="1"
                               :value="hue"
                               @input="hue = +$event.target.value; updateFromHsb(); drawSpectrum();"
                               class="sikds-cp-hue-range">

                        {{-- Swatch + hex input --}}
                        <div class="sikds-cp-bottom">
                            <div class="sikds-cp-swatch" :style="`background:${form.color}`"></div>
                            <div :class="errors.color ? 'sikds-cp-hex-wrap is-error' : 'sikds-cp-hex-wrap'">
                                <span class="sikds-cp-hex-hash">#</span>
                                <input type="text"
                                       class="sikds-cp-hex-input"
                                       :value="form.color.replace('#', '').toUpperCase()"
                                       @input="onHexInput($event.target.value)"
                                       maxlength="6"
                                       spellcheck="false"
                                       autocomplete="off">
                            </div>
                        </div>

                        {{-- Hidden field carries the real hex value on submit --}}
                        <input type="hidden" name="color" :value="form.color">
                    </div>

                    <div x-show="errors.color" x-cloak class="sikds-form-error-pill">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span x-text="errors.color"></span>
                    </div>
                </div>

                <div class="sikds-modal-usage" x-show="editId && editDocCount > 0" x-cloak>
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        {{ __('Ce tag est utilisé dans') }}
                        <strong x-text="editDocCount"></strong>
                        <span x-text="editDocCount > 1 ? i18n.documents : i18n.document"></span>
                    </span>
                </div>

                <div class="sikds-modal-footer">
                    <button type="button" @click="closeModal()" class="sikds-modal-btn-cancel">{{ __('Annuler') }}</button>
                    <button type="submit"
                            class="sikds-modal-btn-confirm"
                            :disabled="!!(errors.name || errors.color || errors.category)"
                            x-text="editId ? i18n.save : i18n.createTag">
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    {{-- Delete confirmation dialog (portalled to <body>) --}}
    <template x-teleport="body">
    <div x-show="deleteTarget"
         x-cloak
         class="sikds-modal-backdrop"
         @click.self="closeDeleteModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="sikds-modal sikds-confirm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-1">

            <button type="button" @click="closeDeleteModal()" class="sikds-confirm-close" aria-label="{{ __('Fermer') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sikds-confirm-head">
                <div class="sikds-confirm-icon">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div>
                    <h3 class="sikds-confirm-title">{{ __('Supprimer le Tag') }}</h3>
                    <p class="sikds-confirm-subtitle">{{ __('Action irréversible') }}</p>
                </div>
            </div>

            <div class="sikds-confirm-body">
                <p>
                    {{ __('Êtes-vous sûr de vouloir supprimer le tag') }}
                    <span class="sikds-confirm-badge" x-show="deleteTarget">
                        <span class="sikds-confirm-badge-dot" :style="deleteTarget ? `background:${deleteTarget.color}` : ''"></span>
                        <span x-text="deleteTarget?.name"></span>
                    </span>
                    ?
                </p>
                <div class="sikds-confirm-notice" x-show="deleteTarget && deleteTarget.count > 0" x-cloak>
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        {{ __('Ce tag est associé à') }}
                        <strong x-text="deleteTarget?.count"></strong>
                        <span x-text="(deleteTarget?.count || 0) > 1 ? i18n.documents : i18n.document"></span>.
                        {{ __('Il sera retiré de') }} <span x-text="(deleteTarget?.count || 0) > 1 ? i18n.theseDocuments : i18n.thisDocument"></span>.
                    </span>
                </div>
            </div>

            <form method="POST" :action="deleteTarget ? '/tags/' + deleteTarget.id : ''" class="sikds-confirm-footer">
                @csrf @method('DELETE')
                <button type="button" @click="closeDeleteModal()" class="sikds-modal-btn-cancel">{{ __('Annuler') }}</button>
                <button type="submit" class="sikds-modal-btn-danger">
                    <i class="fa-regular fa-trash-can"></i>
                    {{ __('Supprimer') }}
                </button>
            </form>
        </div>
    </div>
    </template>

</div>


@endsection
