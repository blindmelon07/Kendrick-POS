<?php

use App\Livewire\Settings\SiteSettings;
use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

it('uploads a background image and stores setting', function () {
    $file = UploadedFile::fake()->image('background.jpg', 1920, 1080)->size(5000);

    Livewire::test(SiteSettings::class)
        ->set('background', $file)
        ->call('saveBackground')
        ->assertSet('bgMessage', 'Background updated successfully.');

    $path = SiteSetting::get('background');
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('uploads a logo and stores setting', function () {
    $file = UploadedFile::fake()->image('logo.png', 128, 128)->size(500);

    Livewire::test(SiteSettings::class)
        ->set('logo', $file)
        ->call('saveLogo')
        ->assertSet('logoMessage', 'Logo updated successfully.');

    $path = SiteSetting::get('logo');
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('removes a custom background', function () {
    $file = UploadedFile::fake()->image('bg.jpg', 1920, 1080);

    Livewire::test(SiteSettings::class)
        ->set('background', $file)
        ->call('saveBackground');

    expect(SiteSetting::get('background'))->not->toBeNull();

    Livewire::test(SiteSettings::class)
        ->call('removeBackground')
        ->assertSet('bgMessage', 'Background removed. Using default.');

    expect(SiteSetting::get('background'))->toBeNull();
});

it('removes a custom logo', function () {
    $file = UploadedFile::fake()->image('logo.png', 128, 128);

    Livewire::test(SiteSettings::class)
        ->set('logo', $file)
        ->call('saveLogo');

    expect(SiteSetting::get('logo'))->not->toBeNull();

    Livewire::test(SiteSettings::class)
        ->call('removeLogo')
        ->assertSet('logoMessage', 'Logo removed. Using default.');

    expect(SiteSetting::get('logo'))->toBeNull();
});

it('validates background must be an image', function () {
    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    Livewire::test(SiteSettings::class)
        ->set('background', $file)
        ->call('saveBackground')
        ->assertHasErrors(['background']);
});
