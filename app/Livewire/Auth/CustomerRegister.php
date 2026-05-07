<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class CustomerRegister extends Component
{
    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Rule('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $user->assignRole('customer');

        Auth::login($user);

        $this->redirect(route('public.my-orders'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.customer-register')
            ->layout('layouts.public');
    }
}
