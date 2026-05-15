<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CoursesTableSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'name' => 'Программирование на Python',
                'icon' => 'programming',
                'age_from' => 10,
                'age_to' => 14,
                'description' => 'Курс по основам программирования на языке Python. Изучим переменные, циклы, функции и создадим свои первые проекты.',
                'price' => 15000,
                'duration_weeks' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Создание сайтов (HTML/CSS)',
                'icon' => 'programming',
                'age_from' => 9,
                'age_to' => 13,
                'description' => 'Научим создавать красивые и современные веб-сайты с нуля. Изучим HTML, CSS и основы дизайна.',
                'price' => 12000,
                'duration_weeks' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Разработка игр на Scratch',
                'icon' => 'pacman',
                'age_from' => 7,
                'age_to' => 10,
                'description' => 'Создаём свои первые игры в визуальной среде Scratch. Развиваем логику и креативное мышление.',
                'price' => 10000,
                'duration_weeks' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Программирование дронов',
                'icon' => 'dron',
                'age_from' => 12,
                'age_to' => 17,
                'description' => 'Управление и программирование дронов.',
                'price' => 20000,
                'duration_weeks' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Робототехника',
                'icon' => 'robot',
                'age_from' => 10,
                'age_to' => 15,
                'description' => 'Сборка и программирование роботов.',
                'price' => 18000,
                'duration_weeks' => 12,
                'is_active' => true,
            ],
        ];

        foreach ($courses as $course) {
            Course::create([
                ...$course,
                'slug' => Str::slug($course['name']) . '-' . uniqid(),
            ]);
        }
    }
}
