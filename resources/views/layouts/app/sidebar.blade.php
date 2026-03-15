<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @canany(['pos.access'])
                <flux:sidebar.group :heading="__('POS')" class="grid">
                    <flux:sidebar.item icon="shopping-cart" :href="route('pos')" :current="request()->routeIs('pos')" wire:navigate>
                        {{ __('Terminal') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('pos.history')" :current="request()->routeIs('pos.history')" wire:navigate>
                        {{ __('Transactions') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcanany

                @canany(['inventory.view', 'inventory.manage'])
                <flux:sidebar.group :heading="__('Inventory')" class="grid">
                    <flux:sidebar.item icon="cube" :href="route('inventory.products')" :current="request()->routeIs('inventory.products')" wire:navigate>
                        {{ __('Products') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-down-tray" :href="route('inventory.stock')" :current="request()->routeIs('inventory.stock')" wire:navigate>
                        {{ __('Stock Management') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('inventory.log')" :current="request()->routeIs('inventory.log')" wire:navigate>
                        {{ __('Adjustment Log') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcanany

                @canany(['deliveries.view', 'deliveries.manage'])
                <flux:sidebar.group :heading="__('Deliveries')" class="grid">
                    <flux:sidebar.item icon="truck" :href="route('deliveries.index')" :current="request()->routeIs('deliveries.*')" wire:navigate>
                        {{ __('Delivery Orders') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-storefront" :href="route('suppliers.index')" :current="request()->routeIs('suppliers.*')" wire:navigate>
                        {{ __('Suppliers') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcanany

                @hasanyrole('manager|admin')
                <flux:sidebar.group :heading="__('Orders')" class="grid">
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('orders.index')" :current="request()->routeIs('orders.*')" wire:navigate>
                        {{ __('Customer Orders') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('customers.index')" :current="request()->routeIs('customers.*')" wire:navigate>
                        {{ __('Customers') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endhasanyrole

                @can('users.manage')
                <flux:sidebar.group :heading="__('Admin')" class="grid">
                    <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                        {{ __('Users') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="circle-stack" :href="route('admin.backup')" :current="request()->routeIs('admin.backup')" wire:navigate>
                        {{ __('Backup & Restore') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Color Theme Picker --}}
            <div
                class="in-data-flux-sidebar-collapsed-desktop:hidden px-3 pb-2"
                x-data="{
                    accent: document.documentElement.getAttribute('data-accent') || 'default',
                    colors: [
                        { key: 'default', hex: '#404040', label: 'Default' },
                        { key: 'blue',    hex: '#2563eb', label: 'Blue'    },
                        { key: 'green',   hex: '#16a34a', label: 'Green'   },
                        { key: 'purple',  hex: '#7c3aed', label: 'Purple'  },
                        { key: 'orange',  hex: '#ea580c', label: 'Orange'  },
                        { key: 'rose',    hex: '#e11d48', label: 'Rose'    },
                    ],
                    setAccent(key) {
                        this.accent = key;
                        localStorage.setItem('pk-accent', key);
                        document.documentElement.setAttribute('data-accent', key);
                    },
                }"
            >
                <p class="mb-1.5 text-xs font-medium text-zinc-400 dark:text-zinc-500">Theme</p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="color in colors" :key="color.key">
                        <button
                            type="button"
                            @click="setAccent(color.key)"
                            class="size-5 rounded-full border-2 transition hover:scale-110 focus:outline-none"
                            :style="`background-color: ${color.hex}`"
                            :class="accent === color.key
                                ? 'border-zinc-900 dark:border-white scale-110 shadow'
                                : 'border-transparent hover:border-zinc-400'"
                            :title="color.label"
                        ></button>
                    </template>
                </div>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
