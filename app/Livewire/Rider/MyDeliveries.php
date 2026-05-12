<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My Deliveries')]
class MyDeliveries extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public bool $showItemsModal = false;
    public ?int $viewingId = null;

    public function updatedStatusFilter(): void { $this->resetPage(); }

    #[Computed]
    public function deliveries(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Order::where('rider_id', auth()->id())
            ->with('items')
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function viewingOrder(): ?Order
    {
        return $this->viewingId
            ? Order::with('items')->find($this->viewingId)
            : null;
    }

    public function viewItems(int $id): void
    {
        $this->viewingId = $id;
        $this->showItemsModal = true;
    }

    public function markOutForDelivery(int $id): void
    {
        $order = Order::where('id', $id)->where('rider_id', auth()->id())->firstOrFail();

        abort_unless(in_array($order->status, ['confirmed', 'preparing']), 422);

        $order->update(['status' => 'out_for_delivery']);
        unset($this->deliveries);
        $this->dispatch('notify', message: 'Order marked as out for delivery.');
    }

    public function markDelivered(int $id): void
    {
        $order = Order::where('id', $id)->where('rider_id', auth()->id())->firstOrFail();

        abort_unless($order->status === 'out_for_delivery', 422);

        $order->update(['status' => 'delivered']);
        unset($this->deliveries);
        $this->dispatch('notify', message: 'Order marked as delivered!');
    }

    private function statusColor(string $status): string
    {
        return match($status) {
            'delivered'        => 'green',
            'out_for_delivery' => 'blue',
            'preparing'        => 'purple',
            'confirmed'        => 'cyan',
            'cancelled'        => 'red',
            default            => 'yellow',
        };
    }

    public function render()
    {
        return view('livewire.rider.my-deliveries')
            ->layout('layouts.app');
    }
}
