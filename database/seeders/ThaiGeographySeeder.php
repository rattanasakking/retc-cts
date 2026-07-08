<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThaiGeographySeeder extends Seeder
{
    /**
     * Populate the province/district/subdistrict reference tables used for
     * cascading location selects and the dashboard map. Source: MIT-licensed
     * kongvut/thai-province-data, compiled down to just the fields this app
     * uses (name + parent id, plus a province-level lat/lng centroid
     * averaged from its subdistricts).
     */
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(__DIR__.'/data/thai-geography.json'),
            true
        );

        DB::table('thai_provinces')->upsert(
            array_map(fn (array $p) => [
                'id' => $p['id'],
                'name_th' => $p['name_th'],
                'lat' => $p['lat'],
                'lng' => $p['lng'],
            ], $data['provinces']),
            ['id']
        );

        foreach (array_chunk($data['districts'], 500) as $chunk) {
            DB::table('thai_districts')->upsert(
                array_map(fn (array $d) => [
                    'id' => $d['id'],
                    'name_th' => $d['name_th'],
                    'province_id' => $d['province_id'],
                ], $chunk),
                ['id']
            );
        }

        foreach (array_chunk($data['subdistricts'], 500) as $chunk) {
            DB::table('thai_subdistricts')->upsert(
                array_map(fn (array $s) => [
                    'id' => $s['id'],
                    'name_th' => $s['name_th'],
                    'district_id' => $s['district_id'],
                ], $chunk),
                ['id']
            );
        }

        $this->command?->info(sprintf(
            'Seeded %d provinces, %d districts, %d subdistricts.',
            count($data['provinces']),
            count($data['districts']),
            count($data['subdistricts'])
        ));
    }
}
