<?php

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('KPI Dashboard')] class extends Component
{
    #[Computed]
    public function todaySales(): float
    {
        return (float) Transaction::where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('total');
    }

    #[Computed]
    public function weeklySales(): float
    {
        return (float) Transaction::where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total');
    }

    #[Computed]
    public function monthlySales(): float
    {
        return (float) Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
    }

    #[Computed]
    public function todayTransactionCount(): int
    {
        return Transaction::where('status', 'completed')
            ->whereDate('created_at', today())
            ->count();
    }

    #[Computed]
    public function grossProfitToday(): float
    {
        $items = TransactionItem::whereHas('transaction', fn ($q) => $q->where('status', 'completed')->whereDate('created_at', today()))
            ->with('product')
            ->get();

        return $items->sum(fn ($item) => ($item->unit_price - (float) $item->product?->cost_price) * $item->quantity);
    }

    #[Computed]
    public function lowStockCount(): int
    {
        return Product::where('is_active', true)
            ->whereRaw('stock_quantity <= reorder_level')
            ->count();
    }

    #[Computed]
    public function outOfStockCount(): int
    {
        return Product::where('is_active', true)
            ->where('stock_quantity', '<=', 0)
            ->count();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, object> */
    #[Computed]
    public function topProducts(): \Illuminate\Support\Collection
    {
        return TransactionItem::selectRaw('product_id, product_name, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->whereHas('transaction', fn ($q) => $q->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    #[Computed]
    public function dailySalesThisWeek(): \Illuminate\Support\Collection
    {
        $start = now()->startOfWeek();

        return collect(range(0, 6))->map(function ($offset) use ($start) {
            $date  = $start->copy()->addDays($offset);
            $total = Transaction::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('total');

            return [
                'day'   => $date->format('D'),
                'total' => (float) $total,
            ];
        });
    }
};
?>

<div class="space-y-6">
    {{-- KPI Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500">Today's Sales</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($this->todaySales, 2) }}</p>
                    <p class="mt-1 text-xs text-zinc-400">{{ $this->todayTransactionCount }} transactions</p>
                </div>
                <flux:icon.banknotes class="size-8 text-green-500 opacity-70" />
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500">This Week</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($this->weeklySales, 2) }}</p>
                </div>
                <flux:icon.chart-bar class="size-8 text-blue-500 opacity-70" />
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500">This Month</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($this->monthlySales, 2) }}</p>
                    <p class="mt-1 text-xs text-zinc-400">Gross Profit: ₱{{ number_format($this->grossProfitToday, 2) }} today</p>
                </div>
                <flux:icon.currency-dollar class="size-8 text-purple-500 opacity-70" />
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500">Stock Alerts</p>
                    <p class="mt-1 text-2xl font-bold {{ $this->lowStockCount > 0 ? 'text-red-600' : 'text-zinc-900 dark:text-white' }}">
                        {{ $this->lowStockCount }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-400">{{ $this->outOfStockCount }} out of stock</p>
                </div>
                <flux:icon.exclamation-triangle class="size-8 {{ $this->lowStockCount > 0 ? 'text-red-500' : 'text-zinc-300' }} opacity-70" />
            </div>
        </flux:card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Weekly Sales Bar --}}
        <flux:card>
            <flux:heading size="sm" class="mb-4">Sales This Week (₱)</flux:heading>
            <div class="flex items-end gap-2 h-32">
                @php $maxDay = max(array_column($this->dailySalesThisWeek->toArray(), 'total') ?: [1]); @endphp
                @foreach ($this->dailySalesThisWeek as $day)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-xs text-zinc-500">{{ number_format($day['total'] / 1000, 1) }}k</span>
                        <div
                            class="w-full rounded-t bg-blue-500 dark:bg-blue-400"
                            style="height: {{ $maxDay > 0 ? round(($day['total'] / $maxDay) * 100) : 0 }}px; min-height: 2px;"
                        ></div>
                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $day['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Top Products --}}
        <flux:card>
            <flux:heading size="sm" class="mb-4">Top 5 Products (This Month)</flux:heading>
            <div class="space-y-2">
                @forelse ($this->topProducts as $product)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-[65%]">{{ $product->product_name }}</span>
                        <div class="text-right">
                            <p class="text-sm font-semibold">{{ number_format($product->total_qty, 0) }} units</p>
                            <p class="text-xs text-zinc-500">₱{{ number_format($product->total_revenue, 2) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">No sales data yet.</p>
                @endforelse
            </div>
        </flux:card>
    </div>

    {{-- Low Stock Alert Table --}}
    @if ($this->lowStockCount > 0)
        <flux:card>
            <flux:heading size="sm" class="mb-4 text-red-600">Low Stock Items</flux:heading>
            @php
                $lowStockProducts = \App\Models\Product::with(['category', 'unit'])
                    ->where('is_active', true)
                    ->whereRaw('stock_quantity <= reorder_level')
                    ->orderBy('stock_quantity')
                    ->limit(10)
                    ->get();
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Product</th>
                            <th class="px-3 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">Current Stock</th>
                            <th class="px-3 py-2 text-right font-medium text-zinc-600 dark:text-zinc-300">Reorder Level</th>
                            <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-300">Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lowStockProducts as $p)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-3 py-2 font-medium text-zinc-800 dark:text-zinc-100">{{ $p->name }}</td>
                                <td class="px-3 py-2 text-right font-bold text-red-600">{{ number_format($p->stock_quantity, 2) }} {{ $p->unit?->abbreviation }}</td>
                                <td class="px-3 py-2 text-right text-zinc-500">{{ number_format($p->reorder_level, 2) }}</td>
                                <td class="px-3 py-2 text-zinc-500">{{ $p->category?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif
</div>