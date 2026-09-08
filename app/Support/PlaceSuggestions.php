<?php

namespace App\Support;

use App\Models\Company;

/**
 * One suggestion list for every field that asks "where do you work / study",
 * merging what the system already knows with what the map can offer.
 *
 * Local rows come first: they are the ones staff have curated or imported
 * from DBD, and picking one keeps the spelling consistent across students —
 * which is what makes the per-employer reporting add up later.
 */
class PlaceSuggestions
{
    public function __construct(private readonly OpenStreetMapCompanies $osm) {}

    /**
     * @return array<int, array{name: string, detail: string, source: string, province: ?string, district: ?string}>
     */
    public function for(string $term, string $kind = Company::COMPANY, int $limit = 12): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $suggestions = [];

        foreach (Company::matching($term)->ofKind($kind)->limit($limit)->get() as $company) {
            $suggestions[Company::normalise($company->name)] = [
                'name' => $company->name,
                'detail' => trim(implode(' · ', array_filter([$company->type, $company->district, $company->province]))),
                'source' => 'local',
                'province' => $company->province,
                'district' => $company->district,
            ];
        }

        // The map fills the long tail: places too old for DBD's dataset, and
        // everything never registered as a juristic person at all.
        foreach ($this->osm->search($term) as $result) {
            $key = Company::normalise($result['name']);

            if (isset($suggestions[$key])) {
                continue;
            }

            $suggestions[$key] = [
                'name' => $result['name'],
                'detail' => $result['detail'],
                'source' => 'osm',
                'province' => $result['province'],
                'district' => $result['district'],
            ];
        }

        return array_slice(array_values($suggestions), 0, $limit);
    }
}
