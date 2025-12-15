<?php

namespace Database\Seeders;

use App\Models\VideoCategory;
use Illuminate\Database\Seeder;

class VideoCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'ACȚIUNE', 'description' => 'Action films with high-octane sequences'],
            ['name' => 'DRAMĂ', 'description' => 'Dramatic stories with emotional depth'],
            ['name' => 'COMEDIE', 'description' => 'Humorous films for entertainment'],
            ['name' => 'HORROR', 'description' => 'Horror and thriller films'],
            ['name' => 'SF', 'description' => 'Science fiction films'],
            ['name' => 'THRILLER', 'description' => 'Suspenseful thriller films'],
            ['name' => 'DOCUMENTAR', 'description' => 'Documentary films'],
            ['name' => 'MUZICĂ', 'description' => 'Music and concert films'],
            ['name' => 'COPII', 'description' => 'Films for children'],
            ['name' => 'SPORT', 'description' => 'Sports films and events'],
            ['name' => 'ROMÂNEȘTI', 'description' => 'Romanian films'],
            ['name' => 'AVENTURĂ', 'description' => 'Adventure films'],
            ['name' => 'ANIMAȚIE', 'description' => 'Animated films'],
            ['name' => 'MISTER', 'description' => 'Mystery films'],
        ];

        foreach ($categories as $category) {
            VideoCategory::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}
