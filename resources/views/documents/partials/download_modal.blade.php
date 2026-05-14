<div
    x-data="downloadWarningModal()"
    @open-download-modal.window="open = true; downloadUrl = $event.detail.downloadUrl || ''; errorMessage = ''; downloading = false"
>
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[60] overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity"
                style="background-color: rgba(10, 10, 10, 0.35);"
                aria-hidden="true"
                @click="open = false"
            ></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative z-10 inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                @click.stop
            >
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ms-4 sm:mt-0 sm:text-start">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                {{ __('Avertissement de traçabilité du document') }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    {{ __("En poursuivant ce téléchargement, ce document officiel sera filigrané de manière permanente avec votre identité complète, votre institution et l'horodatage exact.") }}
                                </p>
                                <p class="mt-2 text-sm font-semibold text-gray-500">
                                    {{ __('Vous êtes pleinement responsable de la conservation sécurisée de ce document. Toute diffusion non autorisée est strictement interdite.') }}
                                </p>
                                <p x-show="errorMessage" x-text="errorMessage" class="mt-2 text-sm font-semibold text-red-600"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button
                        type="button"
                        class="inline-flex w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ms-3 sm:w-auto sm:text-sm"
                        style="background-color: var(--sikds-primary);"
                        @mouseenter="$el.style.backgroundColor='var(--sikds-primary-dark)'"
                        @mouseleave="$el.style.backgroundColor='var(--sikds-primary)'"
                        @click="confirmDownload()"
                        :disabled="downloading"
                    >
                        <span x-text="downloading ? @js(__('Téléchargement...')) : @js(__("J'accepte, télécharger"))"></span>
                    </button>
                    <button
                        type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ms-3 sm:w-auto sm:text-sm"
                        @click="open = false"
                        :disabled="downloading"
                    >
                        {{ __('Annuler') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function downloadWarningModal() {
        const i18n = {
            defaultFilename: @json(__('document.pdf')),
            downloadFailed: @json(__('Impossible de télécharger le document.')),
        };
        return {
            open: false,
            downloadUrl: '',
            downloading: false,
            errorMessage: '',
            extractFilename(disposition) {
                const utf8FilenameMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i);
                if (utf8FilenameMatch) {
                    return decodeURIComponent(utf8FilenameMatch[1]);
                }

                const plainFilenameMatch = disposition.match(/filename=\s*(?:"([^"]+)"|([^;]+))/i);
                const plainFilename = plainFilenameMatch?.[1] || plainFilenameMatch?.[2];

                return (plainFilename || i18n.defaultFilename).trim();
            },
            async confirmDownload() {
                if (!this.downloadUrl || this.downloading) {
                    if (!this.downloadUrl) {
                        this.open = false;
                    }

                    return;
                }

                this.downloading = true;
                this.errorMessage = '';

                try {
                    const response = await fetch(this.downloadUrl, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/pdf, application/octet-stream',
                        },
                    });

                    if (response.redirected) {
                        window.location.href = response.url;

                        return;
                    }

                    if (!response.ok) {
                        throw new Error(i18n.downloadFailed);
                    }

                    const blob = await response.blob();
                    const disposition = response.headers.get('Content-Disposition') || '';
                    const filename = this.extractFilename(disposition);
                    const objectUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');

                    link.href = objectUrl;
                    link.setAttribute('download', filename);
                    link.rel = 'noopener';
                    link.style.display = 'none';

                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(objectUrl);

                    this.open = false;
                } catch (error) {
                    this.errorMessage = error?.message || i18n.downloadFailed;
                } finally {
                    this.downloading = false;
                }
            },
        };
    }
</script>
