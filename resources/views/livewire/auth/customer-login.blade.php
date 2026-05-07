<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-100 dark:border-zinc-700 overflow-hidden">

            {{-- Header --}}
            <div class="bg-gradient-to-br from-amber-500 to-orange-500 px-8 py-8 text-center">
                <img src="{{ asset('images/kendrick.png') }}" alt="Logo"
                     class="size-16 rounded-full object-cover mx-auto mb-3 ring-4 ring-white/30 shadow-lg">
                <h1 class="text-xl font-bold text-white">Welcome Back!</h1>
                <p class="text-amber-100 text-sm mt-1">Sign in to your account to order</p>
            </div>

            {{-- Form --}}
            <div class="px-8 py-8">

                {{-- Session status --}}
                @if(session('status'))
                    <div class="mb-4 text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-4 py-3 rounded-xl">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Email Address
                        </label>
                        <input
                            id="email" name="email" type="email"
                            value="{{ old('email') }}"
                            required autofocus autocomplete="email"
                            placeholder="you@example.com"
                            class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:focus:ring-amber-500 @error('email') border-red-400 @enderror"
                        >
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Password
                            </label>
                            @if(Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 font-medium">
                                Forgot password?
                            </a>
                            @endif
                        </div>
                        <input
                            id="password" name="password" type="password"
                            required autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:focus:ring-amber-500 @error('password') border-red-400 @enderror"
                        >
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center gap-2">
                        <input id="remember" name="remember" type="checkbox"
                               class="size-4 rounded border-zinc-300 text-amber-500 focus:ring-amber-400">
                        <label for="remember" class="text-sm text-zinc-600 dark:text-zinc-400">Remember me</label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="w-full py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 active:scale-[0.98] transition-all shadow-lg shadow-amber-200 dark:shadow-amber-900/30 text-sm">
                        Sign In
                    </button>
                </form>

                {{-- Divider --}}
                <div class="mt-6 pt-6 border-t border-zinc-100 dark:border-zinc-700 text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Don't have an account?
                        <a href="{{ route('customer.register') }}" wire:navigate
                           class="font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                            Create one
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
