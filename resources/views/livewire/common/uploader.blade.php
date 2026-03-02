<!-- html -->
<div>

    <form wire:submit.prevent="uploadFile" class="space-y-4">
        @if(is_null($project->validated_at))
        <div class="flex items-center justify-between w-full">
            <div x-data="{ isUploading: false, progress: 0 }"
                 x-on:livewire-upload-start="isUploading = true"
                 x-on:livewire-upload-finish="isUploading = false"
                 x-on:livewire-upload-error="isUploading = false"
                 x-on:livewire-upload-progress="progress = $event.detail.progress">
                <label for="file" class="sr-only">Choose document</label>

                <input
                    id="file"
                    name="file"
                    type="file"
                    x-ref="file"
                    class="block w-full text-sm text-gray-500
                       file:mr-4 file:py-2 file:px-4
                       file:rounded-full file:border-0
                       file:text-sm file:font-semibold
                       file:bg-blue-50 file:text-blue-700
                       hover:file:bg-blue-100"
                    x-on:change="() => {
                    const f = $refs.file.files && $refs.file.files[0];
                    if (!f) return;

                    if (!($wire && typeof $wire.upload === 'function')) {
                        return;
                    }

                    $wire.upload('file', f,
                        () => {
                            // When temporary upload completes, call the server action to persist
                            $wire.call('uploadFile');
                        },
                        (errorMsg) => {
                            // optional error handling
                        },
                        (event) => {
                            progress = (event && event.detail && event.detail.progress) ? event.detail.progress : 0;
                        }
                    );
                }"
                />

                @error('file') <div class="text-red-600">{{ $message }}</div> @enderror

                <div x-show="isUploading" class="mt-2">
                    <progress max="100" :value="progress"></progress>
                    <span x-text="progress + '%'"></span>
                </div>
            </div>
            @endif
            <div class="mx-4">
                @if($project->getDocumentId($document_type_id) !== null)
                    <a href="{{ $downloadUrl ?? route('downloadDocument', ['id' => $this->project->getDocumentId($this->document_type_id)]) }}"
                       class="text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                        <i class="fa fa-download"></i>
                    </a>
                @else
                    <span class="text-gray-500 italic">No file uploaded</span>
                @endif
            </div>
        </div>


    </form>


</div>
