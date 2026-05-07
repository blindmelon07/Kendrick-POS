<div>
    {{-- Cart flash message --}}
    @if(session('cart_message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-20 right-4 z-50 bg-green-500 text-white px-5 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2">
            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('cart_message') }}
        </div>
    @endif

    {{-- Hero --}}
    <section class="relative bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-zinc-800 dark:via-zinc-800 dark:to-zinc-900 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="inline-flex items-center gap-2 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-sm font-semibold px-4 py-1.5 rounded-full mb-5">
                        <span class="size-2 bg-amber-500 rounded-full animate-pulse"></span>
                        Now Taking Orders
                    </span>
                    <h1 class="text-4xl lg:text-6xl font-bold text-zinc-900 dark:text-white leading-tight">
                        Delicious Food<br>
                        <span class="text-amber-500">Delivered</span> Fresh<br>
                        to Your Door
                    </h1>
                    <p class="mt-5 text-lg text-zinc-600 dark:text-zinc-400 max-w-md">
                        Order your favorite meals online and have them delivered fast. Browse our menu and place your order in minutes.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('public.menu') }}" wire:navigate
                           class="px-8 py-3.5 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors shadow-lg shadow-amber-200 dark:shadow-amber-900/30 inline-flex items-center gap-2">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            Browse Menu
                        </a>
                        @guest
                            <a href="{{ route('customer.register') }}" wire:navigate
                               class="px-8 py-3.5 bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 font-semibold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-600 transition-colors border border-zinc-200 dark:border-zinc-600 shadow">
                                Sign Up Free
                            </a>
                        @endguest
                    </div>
                    <div class="mt-8 flex items-center gap-6 text-sm text-zinc-500 dark:text-zinc-400">
                        <div class="flex items-center gap-1.5">
                            <svg class="size-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            Fresh Ingredients
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="size-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Fast Delivery
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="size-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Easy Ordering
                        </div>
                    </div>
                </div>
                <div class="hidden lg:flex justify-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-amber-300/30 dark:bg-amber-700/20 rounded-full blur-3xl scale-110"></div>
                        <img src="{{ asset('images/kendrick.png') }}" alt="Prince & Ken"
                             class="relative size-72 rounded-full object-cover shadow-2xl ring-8 ring-amber-200/60 dark:ring-amber-700/40">
                    </div>
                </div>
            </div>
        </div>
        {{-- Decorative --}}
        <div class="absolute -top-24 -right-24 size-80 bg-amber-200/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 size-64 bg-orange-200/30 rounded-full blur-2xl pointer-events-none"></div>
    </section>

    {{-- Categories --}}
    @if($categories->count())
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">Browse by Category</h2>
            <p class="mt-2 text-zinc-500 dark:text-zinc-400">Find exactly what you're craving</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @foreach($categories as $category)
            <a href="{{ route('public.menu', ['category' => $category->id]) }}" wire:navigate
               class="group bg-white dark:bg-zinc-800 rounded-2xl p-5 text-center hover:shadow-lg hover:-translate-y-1 transition-all duration-200 border border-zinc-100 dark:border-zinc-700 cursor-pointer">
                <div class="size-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-amber-200 dark:group-hover:bg-amber-900/50 transition-colors">
                    <svg class="size-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div class="font-semibold text-sm text-zinc-800 dark:text-white truncate">{{ $category->name }}</div>
                <div class="text-xs text-zinc-400 mt-0.5">{{ $category->products_count }} {{ Str::plural('item', $category->products_count) }}</div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Featured Products --}}
    @if($featuredProducts->count())
    <section class="bg-zinc-50 dark:bg-zinc-800/40 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-10">
                <div>
                    @if($hasDailyMenu)
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center gap-1.5 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                                <span class="size-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                Today's Menu — {{ now()->format('F j, Y') }}
                            </span>
                        </div>
                        <h2 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">Today's Featured Dishes</h2>
                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">Freshly curated for today</p>
                    @else
                        <h2 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">Featured Items</h2>
                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">Our most popular dishes</p>
                    @endif
                </div>
                <a href="{{ route('public.menu') }}" wire:navigate
                   class="text-sm font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 flex items-center gap-1">
                    View all
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($featuredProducts as $product)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-200 border border-zinc-100 dark:border-zinc-700 group">
                    <div class="h-44 bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-900/20 flex items-center justify-center relative overflow-hidden">
                        <svg class="size-20 text-amber-200 dark:text-amber-800 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        @if($product->category)
                        <span class="absolute top-3 left-3 bg-white/80 dark:bg-zinc-900/80 backdrop-blur text-xs font-semibold text-amber-700 dark:text-amber-300 px-2.5 py-1 rounded-full">
                            {{ $product->category->name }}
                        </span>
                        @endif
                    </div>
                    <div class="p-5">
                        <h3 class="font-semibold text-zinc-900 dark:text-white text-base">{{ $product->name }}</h3>
                        @if($product->description)
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $product->description }}</p>
                        @endif
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-xl font-bold text-zinc-900 dark:text-white">
                                ₱{{ number_format($product->selling_price, 2) }}
                            </span>
                            <button wire:click="addToCart({{ $product->id }})"
                                    wire:loading.attr="disabled" wire:target="addToCart({{ $product->id }})"
                                    class="px-4 py-2 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 active:scale-95 transition-all disabled:opacity-60 inline-flex items-center gap-1.5">
                                <span wire:loading.remove wire:target="addToCart({{ $product->id }})">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </span>
                                <span wire:loading wire:target="addToCart({{ $product->id }})">
                                    <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </span>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- How it works --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-12">
            <h2 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">How It Works</h2>
            <p class="mt-2 text-zinc-500 dark:text-zinc-400">Order your favorite food in 3 easy steps</p>
        </div>
        <div class="grid sm:grid-cols-3 gap-8 relative">
            <div class="hidden sm:block absolute top-8 left-1/3 right-1/3 h-0.5 bg-gradient-to-r from-amber-200 to-amber-200 dark:from-amber-800 dark:to-amber-800"></div>
            @foreach([
                ['emoji' => '🍽️', 'step' => '01', 'title' => 'Browse Menu', 'desc' => 'Explore our wide selection of fresh, delicious food items organized by category.'],
                ['emoji' => '🛒', 'step' => '02', 'title' => 'Add to Cart', 'desc' => 'Pick your favorites, choose quantities, and add them to your cart easily.'],
                ['emoji' => '🚀', 'step' => '03', 'title' => 'Place Order', 'desc' => 'Complete checkout with delivery details and get your food delivered fresh.'],
            ] as $step)
            <div class="text-center relative bg-white dark:bg-zinc-800 rounded-2xl p-8 border border-zinc-100 dark:border-zinc-700 shadow-sm">
                <div class="text-4xl mb-4">{{ $step['emoji'] }}</div>
                <div class="text-xs font-bold text-amber-500 mb-2">STEP {{ $step['step'] }}</div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">{{ $step['title'] }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    @guest
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
        <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-3xl px-8 py-12 text-center text-white shadow-xl shadow-amber-200 dark:shadow-amber-900/30">
            <h2 class="text-2xl lg:text-3xl font-bold">Ready to Order?</h2>
            <p class="mt-3 text-amber-100 max-w-md mx-auto">Sign in to place orders, track deliveries, and enjoy a seamless food ordering experience.</p>
            <div class="mt-8 flex flex-wrap gap-3 justify-center">
                <a href="{{ route('customer.register') }}" wire:navigate
                   class="px-8 py-3.5 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-colors shadow-lg">
                    Create Account
                </a>
                <a href="{{ route('customer.login') }}" wire:navigate
                   class="px-8 py-3.5 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition-colors border border-white/30">
                    Sign In
                </a>
            </div>
        </div>
    </section>
    @endguest
</div>
