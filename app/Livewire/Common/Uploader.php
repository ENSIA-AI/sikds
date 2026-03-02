<?php

namespace App\Livewire\Common;

use Livewire\Component;
use Livewire\WithFileUploads;
use Storage;

class Uploader extends Component
{
    use WithFileUploads;

    public $file;

    public $path = 'uploads/documents';

    public $document_type_id;

    public $filename;

    public $project;

    public $downloadUrl;

    public $url;

    protected $rules = [
        'file' => 'required|file|mimes:pdf|max:5120', // max 5MB
    ];

    public function mount($name, $project, $document_type_id = null)
    {
        $this->filename = $name ?? 'uploaded_file';
        $this->project = $project;
        $this->document_type_id = $document_type_id;
    }

    public function uploadFile()
    {
        $this->validate();

        $extension = $this->file->getClientOriginalExtension();

        $uniqueFilename = auth()->user()->individu.'/'.$this->filename.'.'.$extension;

        try {
            $storedPath = $this->file->storeAs($this->path, $uniqueFilename, 'local');

            $this->url = Storage::disk('local')->url($storedPath);

            $document_data = [
                'document_type_id' => $this->document_type_id,
                'path' => $storedPath,
                'url' => $this->url,
            ];

            $this->project->documents()->create($document_data);

            session()->flash('message', 'File uploaded successfully');

            $this->downloadUrl = route('downloadDocument', ['id' => $this->project->getDocumentId($this->document_type_id)]);

        } catch (\Exception $e) {
            session()->flash('message', 'An error occurred during file storage: '.$e->getMessage());
        }

        // Reset the file input
        $this->file = null;
    }

    public function render()
    {
        return view('livewire.common.uploader');
    }
}
