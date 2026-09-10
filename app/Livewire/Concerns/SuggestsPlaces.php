<?php

namespace App\Livewire\Concerns;

use App\Models\Company;
use App\Models\ThaiProvince;
use App\Support\PlaceSuggestions;
use Throwable;

/**
 * Shared behaviour for every field that names a place — the workplace and the
 * institution, on both the public form and the staff one.
 *
 * Each using component declares which of its properties are place fields and
 * what kind each holds; everything else (searching, picking, remembering the
 * choice) happens here so the four fields cannot drift apart.
 */
trait SuggestsPlaces
{
    /**
     * Suggestions per field, keyed by property name.
     *
     * @var array<string, array<int, array{name: string, detail: string, source: string, province: ?string, district: ?string}>>
     */
    public array $placeSuggestions = [];

    /** Fields that have been searched at least once, so "not found" only shows afterwards. */
    public array $placeSearched = [];

    /**
     * @return array<string, string> property name => Company::COMPANY|INSTITUTION
     */
    abstract protected function placeFields(): array;

    public function refreshPlaceSuggestions(string $field): void
    {
        $kind = $this->placeFields()[$field] ?? null;

        if ($kind === null) {
            return;
        }

        $term = trim((string) $this->{$field});

        if (mb_strlen($term) < 2) {
            $this->placeSuggestions[$field] = [];
            $this->placeSearched[$field] = false;

            return;
        }

        // Suggestions are a convenience. If the lookup fails — most often a
        // database that has not had the latest migrations run — the person
        // typing gets no list, not an error page, and can still type the
        // name in full.
        try {
            $this->placeSuggestions[$field] = app(PlaceSuggestions::class)->for($term, $kind);
        } catch (Throwable $e) {
            report($e);
            $this->placeSuggestions[$field] = [];
        }

        $this->placeSearched[$field] = true;
    }

    public function usePlaceSuggestion(string $field, int $index): void
    {
        $kind = $this->placeFields()[$field] ?? null;
        $suggestion = $this->placeSuggestions[$field][$index] ?? null;

        if ($kind === null || $suggestion === null) {
            return;
        }

        $this->{$field} = $suggestion['name'];
        $this->placeSuggestions[$field] = [];
        $this->placeSearched[$field] = false;

        // A place found on the map is worth keeping, so the next student finds
        // it here rather than going back out to the map for it.
        if ($suggestion['source'] === 'osm') {
            Company::remember($suggestion['name'], [
                'kind' => $kind,
                'source' => 'osm',
                'province' => $suggestion['province'],
                'district' => $suggestion['district'],
            ]);
        }

        $this->applySuggestedProvince($suggestion['province'] ?? null);
    }

    /**
     * Fills the province select when the component has one and the student has
     * not already chosen for themselves.
     */
    private function applySuggestedProvince(?string $province): void
    {
        if (! $province || ! property_exists($this, 'work_province_id') || $this->work_province_id) {
            return;
        }

        $this->work_province_id = ThaiProvince::where('name_th', $province)->value('id');
    }
}
