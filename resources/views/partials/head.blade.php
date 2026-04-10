<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

@php $customBg = \App\Models\SiteSetting::backgroundUrl() ?? asset('images/desk-bg.png'); @endphp
<style>
    .glass-bg {
        background-image:
            linear-gradient(rgba(180,160,220,0.18), rgba(100,80,160,0.22)),
            url('{{ $customBg }}') !important;
        background-size: cover !important;
        background-position: center top !important;
        background-attachment: fixed !important;
    }
</style>
