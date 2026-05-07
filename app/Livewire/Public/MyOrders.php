<?php

namespace App\Livewire\Public;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class MyOrders extends Component
{
    use WithPagination;

    public function render()
    {
        $orders = Order::where(function ($q) {
            $q->where('created_by', auth()->id())
              ->orWhere('client_email', auth()->user()->email);
        })
        ->with('items')
        ->latest()
        ->paginate(10);

        return view('livewire.public.my-orders', compact('orders'))
            ->layout('layouts.public');
    }
}
