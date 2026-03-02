<?php

namespace App\Livewire\Common;

use Livewire\Attributes\On;
use Livewire\Component;

class Loader extends Component
{
    public $loading = false;

    #[On('loader-show')]
    public function showloader()
    {
        $this->loading = true;
    }

    #[On('loader-hide')]
    public function hideloader()
    {
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.common.loader');
    }
}
