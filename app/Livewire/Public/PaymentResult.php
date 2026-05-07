<?php

namespace App\Livewire\Public;

use App\Models\Order;
use Livewire\Component;

class PaymentResult extends Component
{
    public ?Order $record = null;
    public bool $isPaid      = false;
    public bool $isCancelled = false;

    public function mount(int $orderId): void
    {
        $found = Order::where('id', $orderId)
            ->where(function ($q) {
                $q->where('created_by', auth()->id())
                  ->orWhere('client_email', auth()->user()->email);
            })
            ->first();

        if (! $found) {
            $this->redirect(route('public.my-orders'), navigate: true);
            return;
        }

        $this->record      = $found;
        $this->isPaid      = $found->payment_status === 'paid';
        $this->isCancelled = request()->routeIs('payment.cancel');
    }

    public function render()
    {
        return view('livewire.public.payment-result')
            ->layout('layouts.public');
    }
}
