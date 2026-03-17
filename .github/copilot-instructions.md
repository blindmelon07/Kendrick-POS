# Copilot / Agent Instructions for this Repository

Purpose: provide a concise, machine- and human-readable guide for AI agents and contributors to follow project-specific conventions.

Foundational rules (summary)
- This is a Laravel 12 application using PHP 8.3 and these key packages: Fortify, Livewire v4, Flux UI v2, Pest, Pint, Tailwind v4, Laravel Boost tools. See `AGENTS.md` for full versions and guidance.
- Follow existing project conventions when creating files and names; prefer artisan generators when appropriate.

Skills & activation
- When working on Livewire UI, enable `livewire-development` and `fluxui-development`.
- When writing tests, enable `pest-testing`.
- When changing styling, enable `tailwindcss-development`.

Workflow (short)
1. Discover existing conventions
   - Search for `.github/copilot-instructions.md`, `AGENTS.md`, `README.md`, and similar instruction files.
2. Explore the codebase
   - Inspect key folders: `app/`, `resources/views/`, `routes/`, `tests/` and `config/`.
   - Prefer `search-docs` for Laravel/package docs before implementing framework-specific changes.
3. Generate or merge
   - If creating or updating an instruction file, preserve existing guidance (e.g., `AGENTS.md`) and add only repository-specific additions.
4. Iterate
   - Ask for feedback, create follow-up agent customizations (applyTo patterns) if the workspace is large.

Formatting & tooling
- Run `vendor/bin/pint --dirty --format agent` after modifying PHP files.
- Run `php artisan test --compact` (or with a filename filter) for tests related to your change.

What I changed
- Added this file to expose the existing guidance in `AGENTS.md` in a compact template suitable for agents and contributors.

Suggested next agent customizations
- `create-agent: livewire-review` — an agent that runs Livewire-specific checks and relevant tests.
- `create-instruction: frontend.applyTo` — split styling and JS-related instructions into an applyTo pattern for `resources/css/**` and `resources/js/**`.

Example prompts (useful to try)
- "Find Livewire components that use `wire:model` and list untested behaviors." 
- "Add a feature test for the Product model to cover creation and soft-deletes using factories." 

Source of truth: keep `AGENTS.md` as the authoritative, detailed guidance; update this file only with concise, repo-specific additions.
