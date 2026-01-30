<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Land Cruiser', 'Hilux', 'Yaris', 'Prius'],
            'Honda' => ['Accord', 'Civic', 'CR-V', 'Pilot', 'Odyssey', 'Fit', 'HR-V'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'S-Class', 'GLE', 'GLC', 'A-Class'],
            'BMW' => ['3 Series', '5 Series', '7 Series', 'X3', 'X5', 'X1'],
            'Volkswagen' => ['Jetta', 'Passat', 'Tiguan', 'Golf', 'Polo'],
            'Ford' => ['Focus', 'Fusion', 'Escape', 'Explorer', 'F-150', 'Ranger'],
            'Nissan' => ['Altima', 'Sentra', 'Rogue', 'Pathfinder', 'X-Trail'],
            'Hyundai' => ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Accent'],
            'Kia' => ['Optima', 'Sorento', 'Sportage', 'Rio', 'Forte'],
            'Mazda' => ['Mazda3', 'Mazda6', 'CX-5', 'CX-9'],
        ];

        foreach ($brands as $brandName => $models) {
            $brand = Brand::create([
                'name' => $brandName,
                'slug' => Str::slug($brandName),
                'is_active' => true,
            ]);

            foreach ($models as $modelName) {
                CarModel::create([
                    'brand_id' => $brand->id,
                    'name' => $modelName,
                    'slug' => Str::slug($modelName),
                    'is_active' => true,
                ]);
            }
        }
    }
}
