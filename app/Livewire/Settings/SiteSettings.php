<?php

namespace App\Livewire\Settings;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Site Settings')]
class SiteSettings extends Component
{
    use WithFileUploads;

    // No #[Validate] on file properties — rules are passed directly to validate()
    public $logo;

    public $background;

    public string $logoMessage = '';

    public string $bgMessage = '';

    public function saveLogo(): void
    {
        $this->validate(['logo' => 'required|image|max:2048']);

        $old = SiteSetting::get('logo');
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        $path = $this->logo->store('site', 'public');
        SiteSetting::set('logo', $path);

        $this->logo = null;
        $this->logoMessage = 'Logo updated successfully.';
    }

    public function removeLogo(): void
    {
        $old = SiteSetting::get('logo');
        if ($old) {
            Storage::disk('public')->delete($old);
        }
        SiteSetting::remove('logo');
        $this->logoMessage = 'Logo removed. Using default.';
    }

    public function saveBackground(): void
    {
        $this->validate(['background' => 'required|image|max:10240']);

        $old = SiteSetting::get('background');
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        $path = $this->background->store('site', 'public');
        SiteSetting::set('background', $path);

        $this->background = null;
        $this->bgMessage = 'Background updated successfully.';
    }

    public function removeBackground(): void
    {
        $old = SiteSetting::get('background');
        if ($old) {
            Storage::disk('public')->delete($old);
        }
        SiteSetting::remove('background');
        $this->bgMessage = 'Background removed. Using default.';
    }

    public function render()
    {
        return view('livewire.settings.site-settings', [
            'currentLogo' => SiteSetting::logoUrl(),
            'currentBackground' => SiteSetting::backgroundUrl(),
            'hasCustomBg' => (bool) SiteSetting::get('background'),
        ]);
    }
}
