<?php

namespace App\Livewire\Auth;

use Livewire\Component;

class CustomerLogin extends Component
{
    public function render()
    {
        return view('livewire.auth.customer-login')
            ->layout('layouts.public');
    }
}
