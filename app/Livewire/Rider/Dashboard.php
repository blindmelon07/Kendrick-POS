<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Rider Dashboard')]
class Dashboard extends Component
{
    #[Computed]
    public function stats(): array
    {
        $riderId = auth()->id();

        return [
            'assigned'       => Order::where('rider_id', $riderId)
                ->whereIn('status', ['confirmed', 'preparing', 'out_for_delivery'])
                ->count(),
            'out_for_delivery' => Order::where('rider_id', $riderId)
                ->where('status', 'out_for_delivery')
                ->count(),
            'delivered_today' => Order::where('rider_id', $riderId)
                ->where('status', 'delivered')
                ->whereDate('updated_at', today())
                ->count(),
            'total_delivered' => Order::where('rider_id', $riderId)
                ->where('status', 'delivered')
                ->count(),
            'earnings_today'  => Order::where('rider_id', $riderId)
                ->where('status', 'delivered')
                ->whereDate('updated_at', today())
                ->sum('delivery_fee'),
            'earnings_total'  => Order::where('rider_id', $riderId)
                ->where('status', 'delivered')
                ->sum('delivery_fee'),
        ];
    }

    #[Computed]
    public function recentDeliveries(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::where('rider_id', auth()->id())
            ->with('items')
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.rider.dashboard')
            ->layout('layouts.app');
    }
}
