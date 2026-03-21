<?php

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('KPI Dashboard')] class extends Component
{
    public string $period = 'today';

    private function dateRange(): array
    {
        return match ($this->period) {
            'today' => [today()->startOfDay(), today()->endOfDay()],
            'week'  => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year'  => [now()->startOfYear(), now()->endOfYear()],
            default => [today()->startOfDay(), today()->endOfDay()],
        };
    }

    private function previousDateRange(): array
    {
        return match ($this->period) {
            'today' => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay()],
            'week'  => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'year'  => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay()],
        };
    }

    #[Computed]
    public function periodRevenue(): float
    {
        [$start, $end] = $this->dateRange();
        return (float) Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');
    }

    #[Computed]
    public function previousRevenue(): float
    {
        [$start, $end] = $this->previousDateRange();
        return (float) Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');
    }

    #[Computed]
    public function revenueChange(): float|null
    {
        if ($this->previousRevenue == 0) {
            return null;
        }
        return round((($this->periodRevenue - $this->previousRevenue) / $this->previousRevenue) * 100, 1);
    }

    #[Computed]
    public function transactionCount(): int
    {
        [$start, $end] = $this->dateRange();
        return Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    #[Computed]
    public function previousTransactionCount(): int
    {
        [$start, $end] = $this->previousDateRange();
        return Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    #[Computed]
    public function avgTransactionValue(): float
    {
        if ($this->transactionCount === 0) {
            return 0;
        }
        return round($this->periodRevenue / $this->transactionCount, 2);
    }

    #[Computed]
    public function grossProfit(): float
    {
        [$start, $end] = $this->dateRange();
        return (float) DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->join('products as p', 'p.id', '=', 'ti.product_id')
            ->where('t.status', 'completed')
            ->whereBetween('t.created_at', [$start, $end])
            ->selectRaw('SUM((ti.unit_price - p.cost_price) * ti.quantity) as gp')
            ->value('gp') ?? 0;
    }

    #[Computed]
    public function grossMarginPct(): float
    {
        if ($this->periodRevenue == 0) {
            return 0;
        }
        return round(($this->grossProfit / $this->periodRevenue) * 100, 1);
    }

    #[Computed]
    public function totalDiscounts(): float
    {
        [$start, $end] = $this->dateRange();
        $orderDiscount = (float) Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('discount_amount');
        $itemDiscount = (float) DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->where('t.status', 'completed')
            ->whereBetween('t.created_at', [$start, $end])
            ->sum('ti.discount_amount');
        return $orderDiscount + $itemDiscount;
    }

    #[Computed]
    public function paymentBreakdown(): \Illuminate\Support\Collection
    {
        [$start, $end] = $this->dateRange();
        return Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('payment_method, COUNT(*) as tx_count, SUM(total) as revenue')
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();
    }

    #[Computed]
    public function topProducts(): \Illuminate\Support\Collection
    {
        [$start, $end] = $this->dateRange();
        return TransactionItem::selectRaw('product_id, product_name, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->whereHas('transaction', fn ($q) => $q
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start, $end]))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function topCashiers(): \Illuminate\Support\Collection
    {
        [$start, $end] = $this->dateRange();
        return Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('cashier_id, COUNT(*) as tx_count, SUM(total) as revenue')
            ->with('cashier:id,name')
            ->groupBy('cashier_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function salesTrend(): \Illuminate\Support\Collection
    {
        return match ($this->period) {
            'today' => $this->trendToday(),
            'week'  => $this->trendWeek(),
            'month' => $this->trendMonth(),
            'year'  => $this->trendYear(),
            default => collect(),
        };
    }

    private function trendToday(): \Illuminate\Support\Collection
    {
        $rows = Transaction::where('status', 'completed')
            ->whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, SUM(total) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');

        return collect(range(0, 23))->map(fn ($h) => [
            'label' => sprintf('%02d', $h),
            'total' => (float) ($rows[$h] ?? 0),
        ]);
    }

    private function trendWeek(): \Illuminate\Support\Collection
    {
        $start = now()->startOfWeek();
        return collect(range(0, 6))->map(fn ($offset) => [
            'label' => $start->copy()->addDays($offset)->format('D'),
            'total' => (float) Transaction::where('status', 'completed')
                ->whereDate('created_at', $start->copy()->addDays($offset))
                ->sum('total'),
        ]);
    }

    private function trendMonth(): \Illuminate\Support\Collection
    {
        $rows = Transaction::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('DAY(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(1, now()->daysInMonth))->map(fn ($d) => [
            'label' => (string) $d,
            'total' => (float) ($rows[$d] ?? 0),
        ]);
    }

    private function trendYear(): \Illuminate\Support\Collection
    {
        $rows = Transaction::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, SUM(total) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return collect(range(1, 12))->map(fn ($m) => [
            'label' => $months[$m - 1],
            'total' => (float) ($rows[$m] ?? 0),
        ]);
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
};
?>

@php
    $periodLabels = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
    $trendLabel   = ['today' => 'by Hour', 'week' => 'by Day', 'month' => 'by Day', 'year' => 'by Month'][$period];
    $prevLabel    = ['today' => 'Yesterday', 'week' => 'Last Week', 'month' => 'Last Month', 'year' => 'Last Year'][$period];

    $paymentColors = ['cash' => 'bg-emerald-500', 'gcash' => 'bg-blue-500', 'credit_card' => 'bg-violet-500'];
    $paymentIcons  = ['cash' => '💵', 'gcash' => '📱', 'credit_card' => '💳'];
    $paymentMaxRev = $this->paymentBreakdown->max('revenue') ?: 1;

    $topProdMax    = $this->topProducts->max('total_revenue') ?: 1;
    $topCashMax    = $this->topCashiers->max('revenue') ?: 1;

    $trendData     = $this->salesTrend;
    $trendMax      = $trendData->max('total') ?: 1;
    $trendCount    = $trendData->count();
    $labelEvery    = match(true) {
        $trendCount <= 12 => 1,
        $trendCount <= 24 => 4,
        default           => 5,
    };
@endphp

<div class="space-y-6" wire:loading.class="opacity-60">

    {{-- ── Header ── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">KPI Dashboard</flux:heading>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ now()->format('l, F j, Y') }}</p>
        </div>

        {{-- Period Tabs --}}
        <div class="flex overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            @foreach ($periodLabels as $val => $label)
                <button
                    wire:click="$set('period', '{{ $val }}')"
                    class="px-4 py-2 text-sm font-medium transition
                        {{ $period === $val
                            ? 'bg-blue-600 text-white'
                            : 'bg-white text-zinc-600 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}"
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>

    {{-- ── KPI Cards ── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

        {{-- Revenue --}}
        <flux:card>
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Revenue — {{ $periodLabels[$period] }}</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($this->periodRevenue, 2) }}</p>
                    <div class="mt-1 flex items-center gap-1.5 text-xs">
                        @if ($this->revenueChange !== null)
                            @if ($this->revenueChange >= 0)
                                <span class="flex items-center gap-0.5 font-semibold text-emerald-600 dark:text-emerald-400">
                                    ▲ {{ $this->revenueChange }}%
                                </span>
                            @else
                                <span class="flex items-center gap-0.5 font-semibold text-red-500">
                                    ▼ {{ abs($this->revenueChange) }}%
                                </span>
                            @endif
                            <span class="text-zinc-400">vs {{ $prevLabel }}</span>
                        @else
                            <span class="text-zinc-400">No prior period data</span>
                        @endif
                    </div>
                </div>
                <flux:icon.banknotes class="size-8 shrink-0 text-emerald-500 opacity-70" />
            </div>
        </flux:card>

        {{-- Transactions --}}
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Transactions</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($this->transactionCount) }}</p>
                    @if ($this->previousTransactionCount > 0)
                        @php $txChange = round((($this->transactionCount - $this->previousTransactionCount) / $this->previousTransactionCount) * 100, 1); @endphp
                        <p class="mt-1 text-xs {{ $txChange >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                            {{ $txChange >= 0 ? '▲' : '▼' }} {{ abs($txChange) }}% vs {{ $prevLabel }}
                        </p>
                    @else
                        <p class="mt-1 text-xs text-zinc-400">{{ $this->previousTransactionCount }} {{ $prevLabel }}</p>
                    @endif
                </div>
                <flux:icon.receipt-percent class="size-8 shrink-0 text-blue-500 opacity-70" />
            </div>
        </flux:card>

        {{-- Avg Transaction --}}
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Avg Transaction Value</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($this->avgTransactionValue, 2) }}</p>
                    <p class="mt-1 text-xs text-zinc-400">Per completed sale</p>
                </div>
                <flux:icon.calculator class="size-8 shrink-0 text-sky-500 opacity-70" />
            </div>
        </flux:card>

        {{-- Gross Profit --}}
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Gross Profit</p>
                    <p class="mt-1 text-2xl font-bold {{ $this->grossProfit >= 0 ? 'text-zinc-900 dark:text-white' : 'text-red-600' }}">
                        ₱{{ number_format($this->grossProfit, 2) }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-400">Revenue minus cost of goods</p>
                </div>
                <flux:icon.arrow-trending-up class="size-8 shrink-0 text-purple-500 opacity-70" />
            </div>
        </flux:card>

        {{-- Gross Margin % --}}
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Gross Margin</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->grossMarginPct }}%</p>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div
                            class="h-full rounded-full {{ $this->grossMarginPct >= 40 ? 'bg-emerald-500' : ($this->grossMarginPct >= 20 ? 'bg-yellow-500' : 'bg-red-500') }}"
                            style="width: {{ min($this->grossMarginPct, 100) }}%"
                        ></div>
                    </div>
                </div>
                <flux:icon.chart-pie class="size-8 shrink-0 text-indigo-500 opacity-70" />
            </div>
        </flux:card>

        {{-- Discounts --}}
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Discounts Given</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($this->totalDiscounts, 2) }}</p>
                    @if ($this->periodRevenue > 0)
                        <p class="mt-1 text-xs text-zinc-400">
                            {{ round(($this->totalDiscounts / ($this->periodRevenue + $this->totalDiscounts)) * 100, 1) }}% of gross revenue
                        </p>
                    @endif
                </div>
                <flux:icon.tag class="size-8 shrink-0 text-orange-500 opacity-70" />
            </div>
        </flux:card>

    </div>

    {{-- ── Charts Row ── --}}
    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Sales Trend Bar Chart --}}
        <flux:card class="lg:col-span-2">
            <flux:heading size="sm" class="mb-4">Sales {{ $trendLabel }} — {{ $periodLabels[$period] }}</flux:heading>
            <div class="flex h-40 items-end gap-0.5 sm:gap-1">
                @foreach ($trendData as $i => $point)
                    @php $barH = $trendMax > 0 ? max(2, round(($point['total'] / $trendMax) * 140)) : 2; @endphp
                    <div class="group relative flex flex-1 flex-col items-center">
                        {{-- Tooltip --}}
                        @if ($point['total'] > 0)
                            <div class="absolute bottom-full mb-1 hidden whitespace-nowrap rounded bg-zinc-800 px-1.5 py-0.5 text-xs text-white group-hover:block z-10">
                                ₱{{ number_format($point['total'], 0) }}
                            </div>
                        @endif
                        {{-- Bar --}}
                        <div
                            class="w-full rounded-t transition-all {{ $point['total'] > 0 ? 'bg-blue-500 dark:bg-blue-400' : 'bg-zinc-200 dark:bg-zinc-700' }}"
                            style="height: {{ $barH }}px;"
                        ></div>
                        {{-- Label --}}
                        <span class="mt-1 text-zinc-500 dark:text-zinc-400" style="font-size: 9px;">
                            {{ ($i % $labelEvery === 0) ? $point['label'] : '' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Payment Method Breakdown --}}
        <flux:card>
            <flux:heading size="sm" class="mb-4">Payment Methods</flux:heading>
            @if ($this->paymentBreakdown->isEmpty())
                <p class="text-sm text-zinc-400">No transactions yet.</p>
            @else
                <div class="space-y-4">
                    @foreach ($this->paymentBreakdown as $pm)
                        @php
                            $pmName  = str_replace('_', ' ', $pm->payment_method);
                            $pmColor = $paymentColors[$pm->payment_method] ?? 'bg-zinc-400';
                            $pmIcon  = $paymentIcons[$pm->payment_method] ?? '💰';
                            $pmPct   = $paymentMaxRev > 0 ? round(($pm->revenue / $paymentMaxRev) * 100) : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium capitalize text-zinc-700 dark:text-zinc-300">
                                    {{ $pmIcon }} {{ $pmName }}
                                </span>
                                <span class="text-xs text-zinc-400">{{ $pm->tx_count }} txn{{ $pm->tx_count != 1 ? 's' : '' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div class="h-full rounded-full {{ $pmColor }}" style="width: {{ $pmPct }}%"></div>
                                </div>
                                <span class="w-20 shrink-0 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    ₱{{ number_format($pm->revenue, 0) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

    </div>

    {{-- ── Tables Row ── --}}
    <div class="grid gap-4 lg:grid-cols-2">

        {{-- Top 5 Products --}}
        <flux:card>
            <flux:heading size="sm" class="mb-4">Top 5 Products — {{ $periodLabels[$period] }}</flux:heading>
            @if ($this->topProducts->isEmpty())
                <p class="text-sm text-zinc-400">No sales data for this period.</p>
            @else
                <div class="space-y-3">
                    @foreach ($this->topProducts as $i => $product)
                        @php $pct = $topProdMax > 0 ? round(($product->total_revenue / $topProdMax) * 100) : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                        {{ $loop->iteration }}
                                    </span>
                                    <span class="truncate font-medium text-zinc-800 dark:text-zinc-100">{{ $product->product_name }}</span>
                                </div>
                                <div class="ml-2 shrink-0 text-right">
                                    <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">₱{{ number_format($product->total_revenue, 0) }}</p>
                                    <p class="text-xs text-zinc-400">{{ number_format($product->total_qty, 0) }} units</p>
                                </div>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        {{-- Top 5 Cashiers --}}
        <flux:card>
            <flux:heading size="sm" class="mb-4">Top 5 Cashiers — {{ $periodLabels[$period] }}</flux:heading>
            @if ($this->topCashiers->isEmpty())
                <p class="text-sm text-zinc-400">No transactions for this period.</p>
            @else
                <div class="space-y-3">
                    @foreach ($this->topCashiers as $cashier)
                        @php $pct = $topCashMax > 0 ? round(($cashier->revenue / $topCashMax) * 100) : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex min-w-0 items-center gap-2">
                                    <flux:avatar
                                        :name="$cashier->cashier?->name ?? 'Unknown'"
                                        :initials="substr($cashier->cashier?->name ?? '?', 0, 1)"
                                        size="xs"
                                    />
                                    <span class="truncate font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ $cashier->cashier?->name ?? 'Unknown' }}
                                    </span>
                                </div>
                                <div class="ml-2 shrink-0 text-right">
                                    <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">₱{{ number_format($cashier->revenue, 0) }}</p>
                                    <p class="text-xs text-zinc-400">{{ $cashier->tx_count }} txn{{ $cashier->tx_count != 1 ? 's' : '' }}</p>
                                </div>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full bg-purple-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

    </div>

    {{-- ── Stock Alerts ── --}}
    @if ($this->lowStockCount > 0 || $this->outOfStockCount > 0)
        <flux:card>
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="sm" class="text-red-600 dark:text-red-400">
                    Stock Alerts
                </flux:heading>
                <div class="flex gap-2">
                    @if ($this->outOfStockCount > 0)
                        <flux:badge color="red">{{ $this->outOfStockCount }} out of stock</flux:badge>
                    @endif
                    @if ($this->lowStockCount > 0)
                        <flux:badge color="yellow">{{ $this->lowStockCount }} low stock</flux:badge>
                    @endif
                </div>
            </div>
            @php
                $alertProducts = Product::with(['category', 'unit'])
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
                            <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Product</th>
                            <th class="px-3 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">Stock</th>
                            <th class="px-3 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">Reorder At</th>
                            <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Category</th>
                            <th class="px-3 py-2 text-center font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($alertProducts as $p)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-3 py-2 font-medium text-zinc-800 dark:text-zinc-100">{{ $p->name }}</td>
                                <td class="px-3 py-2 text-right font-bold {{ $p->stock_quantity <= 0 ? 'text-red-600' : 'text-orange-500' }}">
                                    {{ number_format($p->stock_quantity, 2) }} {{ $p->unit?->abbreviation }}
                                </td>
                                <td class="px-3 py-2 text-right text-zinc-500">{{ number_format($p->reorder_level, 2) }}</td>
                                <td class="px-3 py-2 text-zinc-500">{{ $p->category?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if ($p->stock_quantity <= 0)
                                        <flux:badge size="sm" color="red">Out of Stock</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="yellow">Low Stock</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

</div>
