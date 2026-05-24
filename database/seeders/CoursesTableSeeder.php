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
                'basic_skills' => ['Синтаксис Python', 'Переменные и типы данных', 'Циклы и условия', 'Функции', 'Проектная практика'],
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
                'basic_skills' => ['Структура HTML', 'Стилизация CSS', 'Адаптивная верстка', 'Публикация веб-страниц'],
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
                'basic_skills' => ['Блоки Scratch', 'Игровая логика', 'Анимация', 'Творческие проекты'],
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
                'basic_skills' => ['Безопасность полетов', 'Основы управления', 'Программирование маршрутов', 'Работа с датчиками'],
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
                'basic_skills' => ['Сборка роботов', 'Моторы и датчики', 'Алгоритмы', 'Управление роботом'],
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
