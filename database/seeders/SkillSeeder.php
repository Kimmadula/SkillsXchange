<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Skill;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $skills = [
            ['name' => 'Web Development', 'category' => 'IT'],
            ['name' => 'Graphic Design', 'category' => 'Design'],
            ['name' => 'Cooking', 'category' => 'Culinary'],
            ['name' => 'Photography', 'category' => 'Arts'],
            ['name' => 'Music Production', 'category' => 'Arts'],
            ['name' => 'Language Learning', 'category' => 'Education'],
            ['name' => 'Fitness Training', 'category' => 'Health'],
            ['name' => 'Digital Marketing', 'category' => 'Business'],
            ['name' => 'Video Editing', 'category' => 'Media'],
            ['name' => 'Writing', 'category' => 'Communication'],
            ['name' => 'Painting', 'category' => 'Arts'],
            ['name' => 'Dancing', 'category' => 'Arts'],
            ['name' => 'Gardening', 'category' => 'Lifestyle'],
            ['name' => 'Carpentry', 'category' => 'Craft'],
            ['name' => 'Sewing', 'category' => 'Craft'],
            ['name' => 'Programming', 'category' => 'IT'],
            ['name' => 'Data Analysis', 'category' => 'IT'],
            ['name' => 'Public Speaking', 'category' => 'Communication'],
            ['name' => 'Drawing', 'category' => 'Arts'],
            ['name' => 'Singing', 'category' => 'Arts'],
        ];

        foreach ($skills as $skill) {
            Skill::firstOrCreate(
                ['name' => $skill['name']],
                ['category' => $skill['category']]
            );
        }
    }
}
