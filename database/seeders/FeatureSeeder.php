<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Feature::create([
            'title' => 'Manage Boutique',
            'title_ar' => 'Manage Boutique',
            'slug' => 'manage-boutique',
            ]);
        Feature::create([
                'title' => 'Manage Sale',
                'title_ar' => 'Manage Sale',
                'slug' => 'manage-sale',
            ]);
        Feature::create([
                'title' => 'Manage Auction',
                'title_ar' => 'Manage Auction',
                'slug' => 'manage-auction',
            ]);
        Feature::create([
                'title' => 'Manage Live',
                'title_ar' => 'Manage Live',
                'slug' => 'manage-live',
            ]);
        Feature::create([
                'title' => 'Manage Bulk',
                'title_ar' => 'Manage Bulk',
                'slug' => 'manage-bulk',
            ]);
        Feature::create([
                'title' => 'Can Purchase Product',
                'title_ar' => 'Can Purchase Product',
                'slug' => 'can-view-sale',
            ]);
        Feature::create(
            [
                'title' => 'Can Participate In Auction',
                'title_ar' => 'Can Participate In Auction',
                'slug' => 'can-participate-auction',
            ]);
        Feature::create([
                'title' => 'Can Participate In Live',
                'title_ar' => 'Can Participate In Live',
                'slug' => 'can-participate-live',
            ]);
        Feature::create([
                'title' => 'Can Purchase Bulk Products',
                'title_ar' => 'Can Purchase Bulk Products',
                'slug' => 'can-purchase-bulk',
            ]);
        Feature::create([
                'title' => 'Can Purchase Discounted Products',
                'title_ar' => 'Can Purchase Discounted Products',
                'slug' => 'can-purchase-discounted',
            ]);

    }
}
