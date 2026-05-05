@extends('layouts.app')
@php($activeNav = 'tags')
@section('page_title', 'Gestion des Tags')
@section('page_subtitle', 'Organiser et catégoriser les documents')
@section('content')

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
            <h2 class="text-2xl sm:text-[28px] font-semibold sikds-ink leading-tight">Tags des Documents</h2>
            <p class="mt-1 text-sm sikds-muted-text">Organiser et catégoriser les documents</p>
        </div>
        <button type="button" @click="openModal()" class="sikds-tags-btn-create shrink-0 self-start sm:self-auto">
            <i class="fa-solid fa-plus text-sm"></i>
            <span>Créer un Tag</span>
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
                                    <p class="text-xs sikds-muted-text">{{ $tag->documents_count }} document{{ $tag->documents_count !== 1 ? 's' : '' }}</p>
                                </div>
                            </div>
                            <div class="sikds-tag-actions">
                                <button type="button" class="sikds-tag-action-btn" title="Modifier"
                                    @click="openEditModal({{ $tag->id }}, @js($tag->name), @js($tag->color ?? '#e0e7ff'), @js($tag->category), {{ $tag->documents_count }})">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="sikds-tag-action-btn sikds-tag-action-btn--danger" title="Supprimer"
                                    @click="openDeleteModal({{ $tag->id }}, @js($tag->name), @js($tag->color ?? '#e0e7ff'), {{ $tag->documents_count }})">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    @if ($tags->isEmpty())
                        <p class="text-sm sikds-muted-text py-2">Aucun tag dans cette catégorie.</p>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Custom tags panel --}}
        <div class="sikds-tag-panel">
            <h3 class="sikds-tag-panel-title">Tags Personnalisés</h3>
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
                                <p class="text-xs sikds-muted-text">{{ $tag->documents_count }} document{{ $tag->documents_count !== 1 ? 's' : '' }}</p>
                            </div>
                        </div>
                        <div class="sikds-tag-actions">
                            <button type="button" class="sikds-tag-action-btn" title="Modifier"
                                @click="openEditModal({{ $tag->id }}, @js($tag->name), @js($tag->color ?? '#e0e7ff'), @js($tag->category), {{ $tag->documents_count }})">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="sikds-tag-action-btn sikds-tag-action-btn--danger" title="Supprimer"
                                @click="openDeleteModal({{ $tag->id }}, @js($tag->name), @js($tag->color ?? '#e0e7ff'), {{ $tag->documents_count }})">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm sikds-muted-text py-2">Aucun tag personnalisé.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Create / Edit modal --}}
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
                    <h3 class="sikds-modal-title" x-text="editId ? 'Modifier le Tag' : 'Créer un Tag'"></h3>
                    <p class="sikds-modal-subtitle"
                       x-text="editId ? 'Modifier les informations du tag' : 'Ajouter un nouveau tag au système'"></p>
                </div>
                <button type="button" @click="closeModal()" class="sikds-modal-close" aria-label="Fermer">
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
                        Nom du tag <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           :value="form.name"
                           @input="form.name = $event.target.value; validateName()"
                           :class="errors.name ? 'sikds-form-input is-error' : 'sikds-form-input'"
                           placeholder="Ex : Urgent, Rapport..."
                           autocomplete="off"
                           required>
                    <div x-show="errors.name" x-cloak class="sikds-form-error-pill">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span x-text="errors.name"></span>
                    </div>
                </div>

                {{-- Category field --}}
                <div class="sikds-form-group">
                    <label class="sikds-form-label">Catégorie</label>
                    <div :class="errors.category ? 'sikds-select-wrap is-error' : 'sikds-select-wrap'"
                         :data-empty="form.category === '' ? 'true' : 'false'">
                        <select x-model="form.category"
                                @change="onCategoryChange()"
                                class="sikds-form-select-native">
                            <option value="">Sélectionner une catégorie</option>
                            <template x-for="c in existingCategories" :key="c">
                                <option :value="c" x-text="categoryLabels[c] || c"></option>
                            </template>
                            <option value="__new__">+ Nouvelle catégorie…</option>
                        </select>
                    </div>

                    <div x-show="form.category === '__new__'" x-cloak class="sikds-new-category">
                        <input type="text"
                               x-model="form.newCategory"
                               @input="validateCategory()"
                               :class="errors.category ? 'sikds-form-input is-error' : 'sikds-form-input'"
                               placeholder="Nom de la nouvelle catégorie"
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
                    <label class="sikds-form-label">Couleur du badge</label>

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
                        Ce tag est utilisé dans
                        <strong x-text="editDocCount"></strong>
                        <span x-text="editDocCount > 1 ? 'documents' : 'document'"></span>
                    </span>
                </div>

                <div class="sikds-modal-footer">
                    <button type="button" @click="closeModal()" class="sikds-modal-btn-cancel">Annuler</button>
                    <button type="submit"
                            class="sikds-modal-btn-confirm"
                            :disabled="!!(errors.name || errors.color || errors.category)"
                            x-text="editId ? 'Enregistrer' : 'Créer le Tag'">
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete confirmation dialog --}}
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

            <button type="button" @click="closeDeleteModal()" class="sikds-confirm-close" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sikds-confirm-head">
                <div class="sikds-confirm-icon">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div>
                    <h3 class="sikds-confirm-title">Supprimer le Tag</h3>
                    <p class="sikds-confirm-subtitle">Action irréversible</p>
                </div>
            </div>

            <div class="sikds-confirm-body">
                <p>
                    Êtes-vous sûr de vouloir supprimer le tag
                    <span class="sikds-confirm-badge" x-show="deleteTarget">
                        <span class="sikds-confirm-badge-dot" :style="deleteTarget ? `background:${deleteTarget.color}` : ''"></span>
                        <span x-text="deleteTarget?.name"></span>
                    </span>
                    ?
                </p>
                <div class="sikds-confirm-notice" x-show="deleteTarget && deleteTarget.count > 0" x-cloak>
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Ce tag est associé à
                        <strong x-text="deleteTarget?.count"></strong>
                        <span x-text="(deleteTarget?.count || 0) > 1 ? 'documents' : 'document'"></span>.
                        Il sera retiré de <span x-text="(deleteTarget?.count || 0) > 1 ? 'ces documents' : 'ce document'"></span>.
                    </span>
                </div>
            </div>

            <form method="POST" :action="deleteTarget ? '/tags/' + deleteTarget.id : ''" class="sikds-confirm-footer">
                @csrf @method('DELETE')
                <button type="button" @click="closeDeleteModal()" class="sikds-modal-btn-cancel">Annuler</button>
                <button type="submit" class="sikds-modal-btn-danger">
                    <i class="fa-regular fa-trash-can"></i>
                    Supprimer
                </button>
            </form>
        </div>
    </div>

</div>

<script>
function tagsPage() {
    return {
        modalOpen:    false,
        editId:       null,
        editDocCount: 0,
        form:         { name: '', color: '#e0e7ff', category: '', newCategory: '' },
        errors:       { name: '', color: '', category: '' },
        deleteTarget: null,

        // Data from server
        existingTags:       @js($allTags),
        existingCategories: @js($existingCategories),
        categoryLabels:     @js($categoryLabels),

        // Color picker internal state (HSV)
        hue:              210,
        sat:              0.13,
        bri:              0.94,
        draggingSpectrum: false,

        /* ── Lifecycle ──────────────────────────────────────── */
        init() {
            @if ($errors->has('name') || $errors->has('color') || $errors->has('category') || $errors->has('new_category'))
                this.form.name        = @js(old('name', ''));
                this.form.color       = @js(old('color', '#e0e7ff'));
                this.form.category    = @js(old('category_mode') === 'new' ? '__new__' : old('category', ''));
                this.form.newCategory = @js(old('new_category', ''));
                this.errors.name     = @js($errors->first('name') ?? '');
                this.errors.color    = @js($errors->first('color') ?? '');
                this.errors.category = @js($errors->first('new_category') ?? $errors->first('category') ?? '');
                this.modalOpen = true;
                this.$nextTick(() => this.initPickerFromHex(this.form.color));
            @endif
        },

        /* ── Normalization helper (accent + case insensitive) ── */
        normalizeName(s) {
            return (s || '')
                .toString()
                .toLowerCase()
                .trim()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, ' ');
        },

        /* ── Modal lifecycle ────────────────────────────────── */
        openModal() {
            this.editId       = null;
            this.editDocCount = 0;
            this.form         = { name: '', color: '#e0e7ff', category: '', newCategory: '' };
            this.errors       = { name: '', color: '', category: '' };
            this.modalOpen    = true;
            this.$nextTick(() => this.initPickerFromHex('#e0e7ff'));
        },

        openEditModal(id, name, color, category, docCount) {
            this.editId       = id;
            this.editDocCount = docCount || 0;
            this.form         = {
                name,
                color,
                category:    category || '',
                newCategory: '',
            };
            this.errors       = { name: '', color: '', category: '' };
            this.modalOpen    = true;
            this.$nextTick(() => this.initPickerFromHex(color));
        },

        closeModal() {
            this.modalOpen    = false;
            this.editId       = null;
            this.editDocCount = 0;
        },

        /* ── Delete confirmation ────────────────────────────── */
        openDeleteModal(id, name, color, count) {
            this.deleteTarget = { id, name, color, count };
        },

        closeDeleteModal() {
            this.deleteTarget = null;
        },

        /* ── Form submission ────────────────────────────────── */
        handleSubmit(form) {
            const nameOk     = this.validateName();
            const colorOk    = this.validateColor();
            const categoryOk = this.validateCategory();
            if (nameOk && colorOk && categoryOk) {
                form.submit();
            }
        },

        /* ── Validation ─────────────────────────────────────── */
        validateName() {
            const raw = (this.form.name || '').trim();
            if (!raw) {
                this.errors.name = 'Le nom est requis.';
                return false;
            }
            const normalized = this.normalizeName(raw);
            const duplicate = this.existingTags.find(
                t => this.normalizeName(t.name) === normalized && t.id !== this.editId
            );
            if (duplicate) {
                this.errors.name = `Un tag similaire existe déjà : « ${duplicate.name} ».`;
                return false;
            }
            this.errors.name = '';
            return true;
        },

        validateColor() {
            const color = (this.form.color || '').toLowerCase();
            const duplicate = this.existingTags.find(
                t => (t.color || '').toLowerCase() === color && t.id !== this.editId
            );
            if (duplicate) {
                this.errors.color = `Cette couleur est déjà utilisée par « ${duplicate.name} ».`;
                return false;
            }
            this.errors.color = '';
            return true;
        },

        validateCategory() {
            if (this.form.category !== '__new__') {
                this.errors.category = '';
                return true;
            }
            const raw = (this.form.newCategory || '').trim();
            if (!raw) {
                this.errors.category = 'Saisis un nom pour la nouvelle catégorie.';
                return false;
            }
            const norm = this.normalizeName(raw);
            const candidateSlug = this.slugifyCategory(raw);
            const clash = this.existingCategories.find(slug => {
                if (slug === candidateSlug) return true;
                if (this.normalizeName(slug) === norm) return true;
                const label = this.categoryLabels[slug] || slug;
                return this.normalizeName(label) === norm;
            });
            if (clash) {
                this.errors.category = 'Cette catégorie existe déjà.';
                return false;
            }
            this.errors.category = '';
            return true;
        },

        slugifyCategory(input) {
            return (input || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        },

        onCategoryChange() {
            if (this.form.category !== '__new__') {
                this.form.newCategory = '';
            }
            this.validateCategory();
        },

        /* ── Color picker: initialise from hex ─────────────── */
        initPickerFromHex(hex) {
            if (!hex || !/^#[0-9a-fA-F]{6}$/.test(hex)) hex = '#e0e7ff';
            const [h, s, b] = this.hexToHsb(hex);
            this.hue = h;
            this.sat = s;
            this.bri = b;
            this.$nextTick(() => this.drawSpectrum());
        },

        /* ── Canvas: spectrum (saturation × brightness) ─────── */
        drawSpectrum() {
            const canvas = this.$refs.spectrumCanvas;
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const W   = canvas.width;
            const H   = canvas.height;

            ctx.fillStyle = `hsl(${this.hue}, 100%, 50%)`;
            ctx.fillRect(0, 0, W, H);

            const wg = ctx.createLinearGradient(0, 0, W, 0);
            wg.addColorStop(0, 'rgba(255,255,255,1)');
            wg.addColorStop(1, 'rgba(255,255,255,0)');
            ctx.fillStyle = wg;
            ctx.fillRect(0, 0, W, H);

            const bg = ctx.createLinearGradient(0, 0, 0, H);
            bg.addColorStop(0, 'rgba(0,0,0,0)');
            bg.addColorStop(1, 'rgba(0,0,0,1)');
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, W, H);

            const cx = this.sat * W;
            const cy = (1 - this.bri) * H;
            ctx.beginPath();
            ctx.arc(cx, cy, 7, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(0,0,0,0.35)';
            ctx.lineWidth   = 2.5;
            ctx.stroke();
            ctx.beginPath();
            ctx.arc(cx, cy, 7, 0, Math.PI * 2);
            ctx.strokeStyle = 'white';
            ctx.lineWidth   = 2;
            ctx.stroke();
        },

        /* ── Drag: spectrum ─────────────────────────────────── */
        startSpectrumDrag(e) {
            this.draggingSpectrum = true;
            this.applySpectrumEvent(e);
        },
        startSpectrumTouch(e) {
            this.draggingSpectrum = true;
            this.applySpectrumEvent(e.touches[0]);
        },
        applySpectrumEvent(e) {
            const canvas = this.$refs.spectrumCanvas;
            if (!canvas) return;
            const r  = canvas.getBoundingClientRect();
            const sx = Math.max(0, Math.min(e.clientX - r.left,  r.width))  / r.width;
            const sy = Math.max(0, Math.min(e.clientY - r.top,   r.height)) / r.height;
            this.sat = sx;
            this.bri = 1 - sy;
            this.updateFromHsb();
            this.drawSpectrum();
        },

        onWindowMouseMove(e) {
            if (this.draggingSpectrum) this.applySpectrumEvent(e);
        },
        onWindowTouchMove(e) {
            if (this.draggingSpectrum) this.applySpectrumEvent(e.touches[0]);
        },
        stopDrag() {
            this.draggingSpectrum = false;
        },

        /* ── Hex input ──────────────────────────────────────── */
        onHexInput(rawVal) {
            const val = rawVal.replace(/[^0-9a-fA-F]/g, '').slice(0, 6);
            const hex = '#' + val;
            this.form.color = hex.toLowerCase();
            if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
                const [h, s, b] = this.hexToHsb(hex);
                this.hue = h;
                this.sat = s;
                this.bri = b;
                this.drawSpectrum();
            }
            this.validateColor();
        },

        updateFromHsb() {
            this.form.color = this.hsbToHex(this.hue, this.sat, this.bri);
            this.validateColor();
        },

        /* ── Color maths ────────────────────────────────────── */
        hsbToHex(h, s, b) {
            const [r, g, bl] = this.hsbToRgb(h, s, b);
            return '#' + [r, g, bl].map(x => x.toString(16).padStart(2, '0')).join('');
        },

        hsbToRgb(h, s, b) {
            h = h / 360;
            const i = Math.floor(h * 6);
            const f = h * 6 - i;
            const p = b * (1 - s);
            const q = b * (1 - f * s);
            const t = b * (1 - (1 - f) * s);
            let r, g, bl;
            switch (i % 6) {
                case 0: r = b;  g = t;  bl = p; break;
                case 1: r = q;  g = b;  bl = p; break;
                case 2: r = p;  g = b;  bl = t; break;
                case 3: r = p;  g = q;  bl = b; break;
                case 4: r = t;  g = p;  bl = b; break;
                default:r = b;  g = p;  bl = q; break;
            }
            return [Math.round(r * 255), Math.round(g * 255), Math.round(bl * 255)];
        },

        hexToHsb(hex) {
            if (!hex || !/^#[0-9a-fA-F]{6}$/.test(hex)) return [210, 0.13, 0.94];
            const r = parseInt(hex.slice(1, 3), 16) / 255;
            const g = parseInt(hex.slice(3, 5), 16) / 255;
            const b = parseInt(hex.slice(5, 7), 16) / 255;
            const max = Math.max(r, g, b);
            const min = Math.min(r, g, b);
            const d   = max - min;
            let h     = 0;
            if (d !== 0) {
                if      (max === r) h = ((g - b) / d % 6) * 60;
                else if (max === g) h = ((b - r) / d + 2) * 60;
                else                h = ((r - g) / d + 4) * 60;
                if (h < 0) h += 360;
            }
            return [h, max === 0 ? 0 : d / max, max];
        },
    };
}
</script>

@endsection
