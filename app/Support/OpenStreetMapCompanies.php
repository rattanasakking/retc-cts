<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Looks a workplace up on OpenStreetMap when it is not in the local table.
 *
 * DBD's open dataset only covers juristic persons registered from 2565
 * onward, so it misses both older companies and everything that was never
 * registered as one — the repair shops, restaurants and factories a lot of
 * graduates actually work for. OSM has those.
 *
 * Nominatim's usage policy forbids autocomplete-style querying, so this runs
 * only when someone presses the search button, and answers are cached so the
 * same wording is never asked twice. Failure is always silent: the form must
 * keep working when OSM is slow, blocked, or down.
 */
class OpenStreetMapCompanies
{
    public function enabled(): bool
    {
        return (bool) config('companies.osm_lookup');
    }

    /**
     * @return array<int, array{name: string, province: ?string, district: ?string, detail: string}>
     */
    public function search(string $term): array
    {
        $term = trim($term);

        if (! $this->enabled() || mb_strlen($term) < 3) {
            return [];
        }

        $key = 'osm-companies:'.md5(mb_strtolower($term));

        return Cache::remember($key, now()->addDays((int) config('companies.osm_cache_days', 30)), function () use ($term) {
            return $this->fetch($term);
        });
    }

    /**
     * @return array<int, array{name: string, province: ?string, district: ?string, detail: string}>
     */
    private function fetch(string $term): array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    // Required by Nominatim — an anonymous caller gets blocked.
                    'User-Agent' => config('companies.osm_user_agent') ?: (config('app.name').' ('.config('app.url').')'),
                    'Accept-Language' => 'th,en',
                ])
                ->get(config('companies.osm_endpoint'), [
                    'q' => $term,
                    'countrycodes' => 'th',
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'limit' => 8,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return $this->toSuggestions($response->json() ?: []);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, array{name: string, province: ?string, district: ?string, detail: string}>
     */
    private function toSuggestions(array $results): array
    {
        $suggestions = [];

        foreach ($results as $result) {
            $name = trim((string) ($result['name'] ?? ''));

            // A result with no name of its own is a road or an area, not a
            // place anyone works at.
            if ($name === '') {
                continue;
            }

            $address = $result['address'] ?? [];

            $suggestions[$name] = [
                'name' => mb_substr($name, 0, 255),
                'province' => $this->cleanProvince($address['province'] ?? $address['state'] ?? null),
                'district' => $this->clean($address['county'] ?? $address['city_district'] ?? $address['district'] ?? null),
                'detail' => mb_substr(trim((string) ($result['display_name'] ?? '')), 0, 255),
            ];
        }

        return array_values($suggestions);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? mb_substr($value, 0, 100) : null;
    }

    /**
     * OSM writes provinces as "จังหวัดร้อยเอ็ด"; the thai_provinces table this
     * app matches against stores them bare.
     */
    private function cleanProvince(?string $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        return trim(preg_replace('/^จังหวัด/u', '', $value)) ?: $value;
    }
}
