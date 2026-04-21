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
        $categories = [
            'Design & creative' => [
                'Graphic Design', 'UI/UX Design', 'Logo Design', 'Illustration', 'Video Editing', 'Motion Graphics', 'Adobe Photoshop', 'Adobe Illustrator', 'Figma', 'Sketch'
            ],
            'Developpement & tech' => [
                'React', 'Laravel', 'Node.js', 'Vue.js', 'Python', 'Django', 'PHP', 'JavaScript', 'TypeScript', 'HTML/CSS', 'MySQL', 'MongoDB', 'PostgreSQL', 'Docker', 'AWS', 'Firebase'
            ],
            'AI & emerging tech' => [
                'Machine Learning', 'Data Science', 'Natural Language Processing', 'OpenAI API', 'TensorFlow', 'PyTorch', 'Blockchain', 'Solidity', 'Web3', 'Chatbot Development'
            ],
            'Marketing' => [
                'Digital Marketing', 'SEO', 'Social Media Marketing', 'Email Marketing', 'Content Marketing', 'Google Ads', 'Facebook Ads', 'Growth Hacking', 'Copywriting'
            ],
            'Writing & content' => [
                'Article Writing', 'Blog Writing', 'Technical Writing', 'Proofreading', 'Translation', 'Creative Writing', 'Scriptwriting', 'Editing'
            ],
            'Admin & support' => [
                'Virtual Assistant', 'Data Entry', 'Customer Support', 'Project Management', 'Administrative Support', 'Microsoft Office', 'Google Workspace'
            ],
        ];

        foreach ($categories as $category => $skills) {
            foreach ($skills as $skillName) {
                Skill::updateOrCreate(
                    ['name' => $skillName],
                    ['category' => $category]
                );
            }
        }
    }
}
