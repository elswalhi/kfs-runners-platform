<?php

namespace Database\Seeders;

use App\Models\Race;
use App\Models\RaceKitPrice;
use Illuminate\Database\Seeder;

class RaceSeeder extends Seeder
{
    public function run(): void
    {
        $races = [
            ['name' => 'Half Marathon', 'slug' => '21k', 'distance_label' => '21 KM', 'category_type' => 'individual', 'sort_order' => 1, 'prices' => ['full' => 650, 'basic' => 450]],
            ['name' => '10K Run', 'slug' => '10k', 'distance_label' => '10 KM', 'category_type' => 'individual', 'sort_order' => 2, 'prices' => ['full' => 650, 'basic' => 450]],
            ['name' => '5K Run', 'slug' => '5k', 'distance_label' => '5 KM', 'category_type' => 'individual', 'sort_order' => 3, 'prices' => ['full' => 600, 'basic' => 400]],
            ['name' => 'Family Run', 'slug' => 'family', 'distance_label' => '2 KM', 'category_type' => 'family', 'sort_order' => 4, 'prices' => ['full' => 500, 'basic' => 350]],
            ['name' => 'Para Race', 'slug' => 'para', 'distance_label' => '2 KM', 'category_type' => 'para', 'sort_order' => 5, 'prices' => ['full' => 500, 'basic' => 350]],
        ];
        foreach ($races as $item) {
            $race = Race::query()->updateOrCreate(['slug' => $item['slug']], collect($item)->except('prices')->all());
            foreach ($item['prices'] as $kit => $price) RaceKitPrice::query()->updateOrCreate(['race_id' => $race->id, 'kit_type' => $kit], ['price' => $price, 'currency' => 'EGP', 'is_active' => true]);
        }
    }
}
