<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skill;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            'React',
            'Laravel',
            'Node.js',
            'Vue.js',
            'Python',
            'Django',
            'SQL',
            'MongoDB',
            'Graphic Design',
            'UI/UX Design',
            'Copywriting',
            'Digital Marketing',
            'SEO',
            'Mobile App Dev',
            'Flutter',
            'React Native',
        ];

        foreach ($skills as $skillName) {
            Skill::firstOrCreate(['name' => $skillName]);
        }
    }
}
