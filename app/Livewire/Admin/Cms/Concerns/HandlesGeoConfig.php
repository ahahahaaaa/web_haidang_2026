<?php

namespace App\Livewire\Admin\Cms\Concerns;

use App\Support\GeoContent;

trait HandlesGeoConfig
{
    public function addGeoDecisionNote(string $path): void
    {
        if (! $this->geoCmsEnabled()) {
            return;
        }

        $path = $this->normalizeGeoConfigPath($path);

        if ($path === null) {
            return;
        }

        $notesPath = $path.'.decision_notes';
        $notes = data_get($this, $notesPath, []);
        $notes = is_array($notes) ? array_values($notes) : [];

        if (count($notes) >= GeoContent::MAX_DECISION_NOTES) {
            return;
        }

        $notes[] = '';
        data_set($this, $notesPath, $notes);
    }

    public function removeGeoDecisionNote(string $path, int $index): void
    {
        if (! $this->geoCmsEnabled()) {
            return;
        }

        $path = $this->normalizeGeoConfigPath($path);

        if ($path === null) {
            return;
        }

        $notesPath = $path.'.decision_notes';
        $notes = data_get($this, $notesPath, []);

        if (! is_array($notes) || ! array_key_exists($index, $notes)) {
            return;
        }

        unset($notes[$index]);
        data_set($this, $notesPath, array_values($notes));
    }

    protected function defaultGeoConfigForm(?array $config = null): array
    {
        return GeoContent::form($config);
    }

    protected function geoValidationRules(string $prefix): array
    {
        if (! $this->geoCmsEnabled()) {
            return [];
        }

        return [
            $prefix.'.is_enabled' => ['boolean'],
            $prefix.'.answer_summary' => ['nullable', 'string', 'max:'.GeoContent::MAX_SUMMARY_LENGTH],
            $prefix.'.decision_notes' => ['nullable', 'array', 'max:'.GeoContent::MAX_DECISION_NOTES],
            $prefix.'.decision_notes.*' => ['nullable', 'string', 'max:'.GeoContent::MAX_DECISION_NOTE_LENGTH],
            $prefix.'.updated_label' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function normalizedGeoConfigForStorage(?array $config = null): array
    {
        return GeoContent::normalize($config);
    }

    protected function geoConfigPayload(?array $config = null): array
    {
        if (! $this->geoCmsEnabled()) {
            return [];
        }

        return ['geo_config' => $this->normalizedGeoConfigForStorage($config)];
    }

    protected function geoCmsEnabled(): bool
    {
        return (bool) config('frontsite_geo.enabled', true);
    }

    protected function normalizeGeoConfigPath(string $path): ?string
    {
        $path = trim($path);

        if (in_array($path, [
            'form.geo_config',
            'categoryForm.geo_config',
            'destinationForm.geo_config',
            'regionForm.geo_config',
        ], true)) {
            return $path;
        }

        if (preg_match('/^form\.blocks\.\d+$/', $path) === 1) {
            return $path;
        }

        return null;
    }
}
