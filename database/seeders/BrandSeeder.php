<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('inventory.brands') as $name) {
            Brand::updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'description' => "Products from the {$name} brand.",
                    'status' => Brand::STATUS_ACTIVE,
                ],
            );
        }
    }
}
