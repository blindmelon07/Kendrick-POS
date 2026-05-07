<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-100 dark:border-zinc-700 overflow-hidden">

            {{-- Header --}}
            <div class="bg-gradient-to-br from-amber-500 to-orange-500 px-8 py-8 text-center">
                <img src="{{ asset('images/kendrick.png') }}" alt="Logo"
                     class="size-16 rounded-full object-cover mx-auto mb-3 ring-4 ring-white/30 shadow-lg">
                <h1 class="text-xl font-bold text-white">Create an Account</h1>
                <p class="text-amber-100 text-sm mt-1">Join us and start ordering today</p>
            </div>

            {{-- Form --}}
            <div class="px-8 py-8">
                <form wire:submit="register" class="space-y-5">

                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            wire:model="name" type="text"
                            required autofocus autocomplete="name"
                            placeholder="Juan dela Cruz"
                            class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:focus:ring-amber-500 @error('name') border-red-400 @enderror"
                        >
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input
                            wire:model="email" type="email"
                            required autocomplete="email"
                            placeholder="you@example.com"
                            class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:focus:ring-amber-500 @error('email') border-red-400 @enderror"
                        >
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input
                            wire:model="password" type="password"
                            required autocomplete="new-password"
                            placeholder="At least 8 characters"
                            class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:focus:ring-amber-500 @error('password') border-red-400 @enderror"
                        >
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input
                            wire:model="password_confirmation" type="password"
                            required autocomplete="new-password"
                            placeholder="Repeat your password"
                            class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:focus:ring-amber-500"
                        >
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 active:scale-[0.98] transition-all shadow-lg shadow-amber-200 dark:shadow-amber-900/30 text-sm disabled:opacity-60 flex items-center justify-center gap-2">
                        <span wire:loading.remove>Create Account</span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Creating account...
        </span>
                    </button>
                </form>

                {{-- Divider --}}
                <div class="mt-6 pt-6 border-t border-zinc-100 dark:border-zinc-700 text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Already have an account?
                        <a href="{{ route('customer.login') }}" wire:navigate
                           class="font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                            Sign in
                        </a>
                    </p>
                </div>

            </div>
        </div>

        {{-- Admin link --}}
        <div class="mt-5 text-center">
            <a href="{{ route('admin.login') }}"
               class="text-xs text-zinc-400 hover:text-zinc-500 dark:hover:text-zinc-300 transition-colors">
                Administrator login →
            </a>
        </div>

    </div>
</div>
