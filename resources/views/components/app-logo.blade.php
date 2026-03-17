@props([
    'sidebar' => false,
])

@php
    $logoSrc = \App\Models\SiteSetting::logoUrl() ?? asset('images/kendrick.png');
@endphp

@if($sidebar)
    <flux:sidebar.brand name="Prince & Ken" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ $logoSrc }}" alt="Prince & Ken" class="size-8 rounded-full object-cover" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Prince & Ken" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ $logoSrc }}" alt="Prince & Ken" class="size-8 rounded-full object-cover" />
        </x-slot>
    </flux:brand>
@endif
