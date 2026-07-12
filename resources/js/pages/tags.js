/**
 * Tags page Alpine component — extracted from resources/views/tags/index.blade.php
 * so the page ships as a Vite module instead of an inline script (CSP hardening).
 * Blade passes i18n, server data and old-input state via #tags-page-config (JSON).
 *
 * Registered on `window` before Alpine.start() runs (app.js starts Alpine on
 * DOMContentLoaded, after all module scripts have executed), so the existing
 * x-data="tagsPage()" binding keeps working unchanged.
 */
function readTagsConfig() {
    const el = document.getElementById('tags-page-config');
    try {
        return el ? JSON.parse(el.textContent || '{}') : {};
    } catch (_) {
        return {};
    }
}

window.tagsPage = function tagsPage() {
    const cfg = readTagsConfig();

    return {
        i18n: cfg.i18n || {},
        modalOpen:    false,
        editId:       null,
        editDocCount: 0,
        form:         { name: '', color: '#e0e7ff', category: '', newCategory: '' },
        errors:       { name: '', color: '', category: '' },
        deleteTarget: null,

        // Data from server
        existingTags:       cfg.existingTags || [],
        existingCategories: cfg.existingCategories || [],
        categoryLabels:     cfg.categoryLabels || {},

        // Color picker internal state (HSV)
        hue:              210,
        sat:              0.13,
        bri:              0.94,
        draggingSpectrum: false,

        /* ── Lifecycle ──────────────────────────────────────── */
        init() {
            // Restore old input + validation errors after a failed submit.
            if (cfg.oldForm) {
                this.form.name        = cfg.oldForm.name;
                this.form.color       = cfg.oldForm.color;
                this.form.category    = cfg.oldForm.category;
                this.form.newCategory = cfg.oldForm.newCategory;
                this.errors.name      = cfg.oldForm.errorName;
                this.errors.color     = cfg.oldForm.errorColor;
                this.errors.category  = cfg.oldForm.errorCategory;
                this.modalOpen = true;
                this.$nextTick(() => this.initPickerFromHex(this.form.color));
            }
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
                this.errors.name = this.i18n.nameRequired;
                return false;
            }
            const normalized = this.normalizeName(raw);
            const duplicate = this.existingTags.find(
                t => this.normalizeName(t.name) === normalized && t.id !== this.editId
            );
            if (duplicate) {
                this.errors.name = this.i18n.similarTagExists.replace(':name', duplicate.name);
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
                this.errors.color = this.i18n.colorAlreadyUsed.replace(':name', duplicate.name);
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
                this.errors.category = this.i18n.newCategoryRequired;
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
                this.errors.category = this.i18n.categoryAlreadyExists;
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
