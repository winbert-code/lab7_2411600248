<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('inventory.categories') as $name) {
            Category::updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'description' => "Computer parts in the {$name} category.",
                    'status' => Category::STATUS_ACTIVE,
                ],
            );
        }
    }
}
