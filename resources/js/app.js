// Apply stored accent immediately to prevent flash of unstyled content
(function () {
    const accent = localStorage.getItem('pk-accent') ?? 'default';
    if (accent !== 'default') {
        document.documentElement.setAttribute('data-accent', accent);
    }

    // Default to dark mode if user has never chosen an appearance
    if (!localStorage.getItem('flux.appearance')) {
        localStorage.setItem('flux.appearance', 'dark');
    }
}());
