<div>
    {{-- Cart flash message --}}
    @if(session('cart_message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed top-20 right-4 z-50 bg-green-500 text-white px-5 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2">
            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('cart_message') }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">
                @if($activeCategory)
                    {{ $activeCategory->name }}
                @else
                    Our Menu
                @endif
            </h1>
            <p class="mt-1 text-zinc-500 dark:text-zinc-400">Fresh food made with quality ingredients</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">

            {{-- Sidebar: categories + search --}}
            <aside class="lg:w-60 shrink-0">
                {{-- Search --}}
                <div class="mb-6">
                    <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2 block">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search food..."
                               class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:focus:ring-amber-500">
                    </div>
                </div>

                {{-- Categories --}}
                <div>
                    <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2 block">Categories</label>
                    <nav class="space-y-1">
                        <button wire:click="$set('category', null)"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors
                                       {{ !$category ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                            <span>All Items</span>
                        </button>
                        @foreach($categories as $cat)
                        <button wire:click="$set('category', {{ $cat->id }})"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors
                                       {{ $category == $cat->id ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                            <span>{{ $cat->name }}</span>
                            <span class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 px-1.5 py-0.5 rounded-full">{{ $cat->products_count }}</span>
                        </button>
                        @endforeach
                    </nav>
                </div>
            </aside>

            {{-- Product grid --}}
            <div class="flex-1 min-w-0">
                {{-- Active filters --}}
                @if($search || $category)
                <div class="flex items-center gap-2 mb-5 flex-wrap">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Filters:</span>
                    @if($search)
                    <span class="inline-flex items-center gap-1 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-medium px-3 py-1 rounded-full">
                        "{{ $search }}"
                        <button wire:click="$set('search', '')" class="hover:text-red-500">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                    @endif
                    @if($category && $activeCategory)
                    <span class="inline-flex items-center gap-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-xs font-medium px-3 py-1 rounded-full">
                        {{ $activeCategory->name }}
                        <button wire:click="$set('category', null)" class="hover:text-red-500">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                    @endif
                </div>
                @endif

                @if($products->isEmpty())
                <div class="text-center py-20">
                    <div class="size-16 bg-zinc-100 dark:bg-zinc-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="size-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-zinc-700 dark:text-zinc-300">No items found</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Try adjusting your search or category filter.</p>
                    <button wire:click="$set('search', ''); $set('category', null)"
                            class="mt-4 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">
                        Clear Filters
                    </button>
                </div>
                @else
                <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($products as $product)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl overflow-hidden border border-zinc-100 dark:border-zinc-700 shadow-sm hover:shadow-lg transition-all duration-200 group flex flex-col">
                        <div class="h-40 bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-900/20 flex items-center justify-center relative overflow-hidden shrink-0">
                            <svg class="size-16 text-amber-200 dark:text-amber-800 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            @if($product->category)
                            <span class="absolute top-2.5 left-2.5 bg-white/80 dark:bg-zinc-900/80 backdrop-blur text-[11px] font-semibold text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded-full">
                                {{ $product->category->name }}
                            </span>
                            @endif
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $product->name }}</h3>
                            @if($product->description)
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 flex-1">{{ $product->description }}</p>
                            @else
                            <div class="flex-1"></div>
                            @endif
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-lg font-bold text-zinc-900 dark:text-white">
                                    ₱{{ number_format($product->selling_price, 2) }}
                                </span>
                                <button wire:click="addToCart({{ $product->id }})"
                                        wire:loading.attr="disabled" wire:target="addToCart({{ $product->id }})"
                                        class="px-3 py-2 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 active:scale-95 transition-all disabled:opacity-60 inline-flex items-center gap-1.5">
                                    <span wire:loading.remove wire:target="addToCart({{ $product->id }})">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </span>
                                    <span wire:loading wire:target="addToCart({{ $product->id }})">
                                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                    </span>
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
