<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Site Settings')"
        :subheading="__('Customize the app logo and background image')"
    >
        {{-- ── APP LOGO ── --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="sm">{{ __('App Logo') }}</flux:heading>
                <flux:subheading size="sm">{{ __('Shown in the sidebar and login page. Recommended: square image, at least 128×128px.') }}</flux:subheading>
            </div>

            {{-- Current logo preview --}}
            <div class="flex items-center gap-4">
                <img
                    src="{{ $currentLogo ?? asset('images/kendrick.png') }}"
                    alt="Current logo"
                    class="size-16 rounded-full object-cover ring-2 ring-white/30 shadow-md"
                />
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $currentLogo ? __('Custom logo active') : __('Using default logo') }}
                </div>
            </div>

            {{-- Upload form --}}
            <form wire:submit="saveLogo" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Choose new logo') }}</label>
                    <input
                        type="file"
                        wire:model="logo"
                        accept="image/*"
                        class="block w-full text-sm text-zinc-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                    />
                </div>
                @error('logo')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror

                @if ($logoMessage)
                    <flux:badge color="green" size="sm">{{ $logoMessage }}</flux:badge>
                @endif

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" size="sm">
                        {{ __('Upload Logo') }}
                    </flux:button>
                    @if ($currentLogo)
                        <flux:button
                            type="button"
                            wire:click="removeLogo"
                            wire:confirm="Remove custom logo and revert to default?"
                            variant="ghost"
                            size="sm"
                        >
                            {{ __('Remove') }}
                        </flux:button>
                    @endif
                </div>
            </form>
        </div>

        <flux:separator class="my-6" />

        {{-- ── BACKGROUND IMAGE ── --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="sm">{{ __('Background Image') }}</flux:heading>
                <flux:subheading size="sm">{{ __('Full-screen background for the entire app. Recommended: 1920×1080px or wider.') }}</flux:subheading>
            </div>

            {{-- Current background preview --}}
            <div class="relative w-full h-36 rounded-xl overflow-hidden ring-1 ring-white/20 shadow-md">
                <img
                    src="{{ $currentBackground ?? asset('images/desk-bg.png') }}"
                    alt="Current background"
                    class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-black/20 flex items-end p-2">
                    <span class="text-xs text-white/80 font-medium">
                        {{ $hasCustomBg ? __('Custom background active') : __('Using default background') }}
                    </span>
                </div>
            </div>

            {{-- Upload form --}}
            <form wire:submit="saveBackground" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Choose new background') }}</label>
                    <input
                        type="file"
                        wire:model="background"
                        accept="image/*"
                        class="block w-full text-sm text-zinc-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                    />
                </div>
                @error('background')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror

                @if ($bgMessage)
                    <flux:badge color="green" size="sm">{{ $bgMessage }}</flux:badge>
                @endif

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" size="sm">
                        {{ __('Upload Background') }}
                    </flux:button>
                    @if ($hasCustomBg)
                        <flux:button
                            type="button"
                            wire:click="removeBackground"
                            wire:confirm="Remove custom background and revert to default?"
                            variant="ghost"
                            size="sm"
                        >
                            {{ __('Revert to Default') }}
                        </flux:button>
                    @endif
                </div>
            </form>
        </div>
    </x-settings.layout>
</section>
