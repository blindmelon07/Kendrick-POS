<div class="flex h-full w-full flex-col gap-6 p-6">

        {{-- Flash --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="flex items-center gap-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl text-sm font-medium">
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Header row --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">Daily Featured Menu</flux:heading>
                <flux:subheading>Set which dishes are featured on the public home page each day.</flux:subheading>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Date picker --}}
                <div class="flex items-center gap-2">
                    <flux:icon.calendar class="size-4 text-zinc-400" />
                    <input wire:model.live="selectedDate" type="date"
                           class="px-3 py-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <flux:button wire:click="openAddModal" variant="primary" icon="plus">
                    Add Dish
                </flux:button>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->featuredToday->count() }}</div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Featured Dishes</div>
            </div>
            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $selectedDate === today()->toDateString() ? 'Today' : \Carbon\Carbon::parse($selectedDate)->format('M d') }}
                </div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Selected Date</div>
            </div>
            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                <div class="text-2xl font-bold text-amber-500">{{ $this->availableProducts->count() }}</div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Available to Add</div>
            </div>
        </div>

        {{-- Featured list --}}
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">
                    Featured Dishes
                    @if($selectedDate === today()->toDateString())
                        <span class="ml-2 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-0.5 rounded-full">Today</span>
                    @endif
                </flux:heading>
                @if($this->featuredToday->count() > 0)
                    <flux:button wire:click="clearDate" wire:confirm="Remove all dishes from this date?" variant="ghost" size="sm" class="text-red-500 hover:text-red-600">
                        Clear All
                    </flux:button>
                @endif
            </div>

            @if($this->featuredToday->isEmpty())
                <div class="text-center py-12">
                    <div class="size-14 bg-zinc-100 dark:bg-zinc-700 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <flux:icon.squares-2x2 class="size-7 text-zinc-400" />
                    </div>
                    <p class="font-medium text-zinc-600 dark:text-zinc-400">No featured dishes for this date</p>
                    <p class="text-sm text-zinc-400 mt-1">Click "Add Dish" to feature products on the home page.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($this->featuredToday as $entry)
                    <div class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-xl px-4 py-3 group">
                        {{-- Sort badge --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="size-7 flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-xs font-bold rounded-lg">
                                {{ $loop->iteration }}
                            </span>
                        </div>

                        {{-- Product thumb --}}
                        <div class="size-12 bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl flex items-center justify-center shrink-0">
                            <flux:icon.archive-box class="size-6 text-amber-400" />
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-zinc-900 dark:text-white text-sm truncate">{{ $entry->product->name }}</div>
                            <div class="flex items-center gap-3 mt-0.5">
                                @if($entry->product->category)
                                <span class="text-xs text-zinc-400">{{ $entry->product->category->name }}</span>
                                @endif
                                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">₱{{ number_format($entry->product->selling_price, 2) }}</span>
                                <span class="text-xs text-zinc-400">Stock: {{ (int) $entry->product->stock_quantity }}</span>
                            </div>
                        </div>

                        {{-- Sort order input --}}
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="text-xs text-zinc-400">Order</span>
                            <input type="number" min="0" max="99"
                                   value="{{ $entry->sort_order }}"
                                   wire:change="updateOrder({{ $entry->id }}, $event.target.value)"
                                   class="w-14 px-2 py-1 text-xs text-center bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400">
                        </div>

                        {{-- Remove --}}
                        <flux:button wire:click="remove({{ $entry->id }})" wire:confirm="Remove this dish from the daily menu?"
                                     variant="ghost" size="sm" icon="trash" class="text-red-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                    </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        {{-- Copy from yesterday shortcut --}}
        <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
            <flux:icon.arrow-path class="size-4 shrink-0" />
            <span>Copy menu from:</span>
            <button wire:click="copyFromDate('{{ today()->subDay()->toDateString() }}')"
                    class="text-amber-600 hover:text-amber-700 dark:text-amber-400 font-medium">
                Yesterday
            </button>
            <span>·</span>
            <button wire:click="copyFromDate('{{ today()->subWeek()->toDateString() }}')"
                    class="text-amber-600 hover:text-amber-700 dark:text-amber-400 font-medium">
                Last week
            </button>
        </div>

    {{-- Add dish modal --}}
    <flux:modal wire:model="showAddModal" name="add-dish" class="max-w-xl">
        <div class="p-6">
            <flux:heading size="lg" class="mb-1">Add Dish to Menu</flux:heading>
            <p class="text-sm text-zinc-400 dark:text-zinc-500 mb-4">Only food dishes are shown. Store products are hidden.</p>

            {{-- Search --}}
            <div class="relative mb-3">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.300ms="searchProduct"
                       type="text" placeholder="Search dishes..."
                       class="w-full pl-9 pr-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>

            {{-- Category tabs --}}
            <div class="flex gap-1.5 flex-wrap mb-3">
                <button wire:click="$set('filterCategoryId', null)"
                        class="px-3 py-1 text-xs font-semibold rounded-full transition-colors
                               {{ !$filterCategoryId ? 'bg-amber-500 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                    All
                </button>
                @foreach($this->menuCategories as $cat)
                <button wire:click="$set('filterCategoryId', {{ $cat->id }})"
                        class="px-3 py-1 text-xs font-semibold rounded-full transition-colors
                               {{ $filterCategoryId == $cat->id ? 'bg-amber-500 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>

            {{-- Dish list --}}
            <div class="space-y-1 max-h-72 overflow-y-auto mb-4 pr-1" wire:loading.class="opacity-50">
                @forelse($this->availableProducts as $product)
                <label class="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer
                              hover:bg-zinc-50 dark:hover:bg-zinc-700/60
                              border border-transparent
                              has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50 dark:has-[:checked]:bg-amber-900/20
                              transition-all">
                    <input type="radio" wire:model="addProductId" value="{{ $product->id }}"
                           class="shrink-0 accent-amber-500">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                            {{ $product->name }}
                        </div>
                        @if($product->description)
                        <div class="text-xs text-zinc-400 truncate mt-0.5">{{ $product->description }}</div>
                        @endif
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-sm font-bold text-amber-600 dark:text-amber-400">
                            ₱{{ number_format($product->selling_price, 2) }}
                        </div>
                        @if($product->category)
                        <div class="text-[10px] text-zinc-400 mt-0.5">{{ $product->category->name }}</div>
                        @endif
                    </div>
                </label>
                @empty
                <div class="text-center py-8">
                    <div class="text-2xl mb-2">🍽️</div>
                    <p class="text-sm text-zinc-400">No dishes found.</p>
                    @if($searchProduct)
                    <button wire:click="$set('searchProduct', '')" class="mt-2 text-xs text-amber-500 hover:underline">Clear search</button>
                    @endif
                </div>
                @endforelse
            </div>

            {{-- Display order + error --}}
            <div class="mb-5">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-1.5">Display Order</label>
                <input wire:model="addSortOrder" type="number" min="0"
                       class="w-28 px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-amber-400">
                @error('addProductId')
                    <p class="mt-1.5 text-xs text-red-500">Please select a dish first.</p>
                @enderror
            </div>

            <div class="flex gap-3 justify-end pt-2 border-t border-zinc-100 dark:border-zinc-700">
                <flux:button wire:click="$set('showAddModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="addToMenu" variant="primary">Add to Menu</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
