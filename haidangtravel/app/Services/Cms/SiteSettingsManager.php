<?php

namespace App\Services\Cms;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\SiteSetting;

class SiteSettingsManager
{
    protected const DEFAULT_THEME = 'haidangtravel';

    /** @var array<string, \Src\Domains\Cms\Models\Menu|null> */
    protected array $menuDefinitions = [];

    protected ?SiteSetting $settings = null;

    /** @var array<string, \Illuminate\Database\Eloquent\Collection<int, \Src\Domains\Cms\Models\MenuItem>> */
    protected array $menus = [];

    /** @var array<string, bool> */
    protected array $tablePresence = [];

    public function activeTheme(): string
    {
        return $this->normalizeTheme($this->current()->active_theme);
    }

    public function current(): SiteSetting
    {
        if ($this->settings) {
            return $this->settings;
        }

        if (! $this->hasTable('site_settings')) {
            return $this->settings = new SiteSetting([
                'active_theme' => self::DEFAULT_THEME,
                'site_name' => config('app.name', 'Haidang Travel'),
            ]);
        }

        $settings = SiteSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'active_theme' => self::DEFAULT_THEME,
                'company_name' => 'Du Lịch Hải Đăng Travel',
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
                'primary_email' => config('mail.from.address'),
                'site_name' => 'Haidang Travel',
            ],
        );

        $normalizedTheme = $this->normalizeTheme($settings->active_theme);

        if ($settings->active_theme !== $normalizedTheme) {
            $settings->forceFill(['active_theme' => $normalizedTheme])->saveQuietly();
        }

        return $this->settings = $settings;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \Src\Domains\Cms\Models\MenuItem>
     */
    public function menu(string $location): Collection
    {
        $menu = $this->menuDefinition($location);

        if (! $menu) {
            return $this->menus[$location] = new Collection;
        }

        if (array_key_exists($location, $this->menus)) {
            return $this->menus[$location];
        }

        return $this->menus[$location] = $menu->items;
    }

    public function menuDefinition(string $location): ?Menu
    {
        if (array_key_exists($location, $this->menuDefinitions)) {
            return $this->menuDefinitions[$location];
        }

        if (! $this->hasTable('menus') || ! $this->hasTable('menu_items')) {
            return $this->menuDefinitions[$location] = null;
        }

        return $this->menuDefinitions[$location] = Menu::query()
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('order')])
            ->where('location', $location)
            ->first();
    }

    public function refresh(): void
    {
        $this->menuDefinitions = [];
        $this->menus = [];
        $this->settings = null;
    }

    protected function hasTable(string $table): bool
    {
        if (! array_key_exists($table, $this->tablePresence)) {
            $this->tablePresence[$table] = Schema::hasTable($table);
        }

        return $this->tablePresence[$table];
    }

    protected function normalizeTheme(?string $theme): string
    {
        $theme = filled($theme) ? trim((string) $theme) : self::DEFAULT_THEME;

        return $this->themeExists($theme) ? $theme : self::DEFAULT_THEME;
    }

    protected function themeExists(string $theme): bool
    {
        return is_dir(resource_path('views/themes/'.$theme));
    }
}
