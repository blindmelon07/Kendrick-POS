<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-gray-50 dark:bg-zinc-900 antialiased" x-data>

    {{-- Navbar --}}
    <nav class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                {{-- Logo --}}
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3">
                    <img src="{{ asset('images/kendrick.png') }}" alt="Prince & Ken" class="size-9 rounded-full object-cover shadow">
                    <div class="hidden sm:block leading-tight">
                        <div class="font-semibold text-zinc-900 dark:text-white text-sm">Prince & Ken</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Food Ordering</div>
                    </div>
                </a>

                {{-- Center nav links --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('home') }}" wire:navigate
                       class="px-4 py-2 rounded-lg text-sm font-medium text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-zinc-700 transition-colors">
                        Home
                    </a>
                    <a href="{{ route('public.menu') }}" wire:navigate
                       class="px-4 py-2 rounded-lg text-sm font-medium text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-zinc-700 transition-colors">
                        Menu
                    </a>
                </div>

                {{-- Right actions --}}
                <div class="flex items-center gap-1">

                    {{-- Cart --}}
                    <a href="{{ route('public.cart') }}" wire:navigate
                       class="relative p-2 rounded-lg text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-zinc-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        @php $cartCount = \App\Services\CartService::count(); @endphp
                        @if($cartCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 bg-amber-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1">
                                {{ $cartCount > 99 ? '99+' : $cartCount }}
                            </span>
                        @endif
                    </a>

                    @auth
                        {{-- User dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                                <div class="size-7 rounded-full bg-amber-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <span class="hidden sm:block max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 class="absolute right-0 mt-2 w-52 bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50">
                                <div class="px-4 py-2 border-b border-zinc-100 dark:border-zinc-700">
                                    <div class="text-xs text-zinc-400">Signed in as</div>
                                    <div class="text-sm font-medium text-zinc-800 dark:text-white truncate">{{ auth()->user()->email }}</div>
                                </div>
                                <a href="{{ route('public.my-orders') }}" wire:navigate
                                   class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    My Orders
                                </a>
                                <hr class="my-1 border-zinc-200 dark:border-zinc-700">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 text-left">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('customer.login') }}" wire:navigate
                           class="hidden sm:inline-flex px-4 py-2 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                            Log in
                        </a>
                        <a href="{{ route('customer.register') }}" wire:navigate
                           class="px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 text-white hover:bg-amber-600 transition-colors shadow-sm">
                            Register
                        </a>
                    @endauth

                    {{-- Mobile menu toggle --}}
                    <button x-data @click="$dispatch('toggle-mobile-menu')"
                            class="md:hidden p-2 rounded-lg text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-data="{ open: false }" @toggle-mobile-menu.window="open = !open"
             x-show="open" x-transition class="md:hidden border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-4 py-3 space-y-1">
            <a href="{{ route('home') }}" wire:navigate
               class="block px-3 py-2 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">Home</a>
            <a href="{{ route('public.menu') }}" wire:navigate
               class="block px-3 py-2 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">Menu</a>
            @auth
                <a href="{{ route('public.my-orders') }}" wire:navigate
                   class="block px-3 py-2 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">My Orders</a>
            @else
                <a href="{{ route('customer.login') }}" wire:navigate
                   class="block px-3 py-2 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">Log in</a>
                <a href="{{ route('customer.register') }}" wire:navigate
                   class="block px-3 py-2 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">Register</a>
            @endauth
        </div>
    </nav>

    {{-- Page content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                &copy; {{ date('Y') }} Prince &amp; Ken Construction and Marketing Corp. All rights reserved.
            </p>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
