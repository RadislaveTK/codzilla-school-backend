<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoGroupSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::updateOrCreate(
            ['slug' => 'demo-python-starter'],
            [
                'name' => 'Демо Python Starter',
                'icon' => 'programming',
                'age_from' => 10,
                'age_to' => 14,
                'description' => 'Демо-курс для проверки групп и учеников.',
                'basic_skills' => [
                    'Основы Python',
                    'Переменные и типы данных',
                    'Циклы и условия',
                    'Простые консольные проекты',
                ],
                'price' => 15000,
                'duration_weeks' => 8,
                'is_active' => true,
            ]
        );

        Group::where('name', 'Demo Python Group')->update(['name' => 'Демо-группа Python']);
        Student::where('full_name', 'Demo Student One')->update(['full_name' => 'Демо Ученик Один']);
        Student::where('full_name', 'Demo Student Two')->update(['full_name' => 'Демо Ученик Два']);

        $group = Group::updateOrCreate(
            ['name' => 'Демо-группа Python'],
            [
                'course_id' => $course->id,
                'max_students' => 10,
                'current_students' => 0,
                'status' => 'active',
                'description' => 'Демо-группа с двумя учениками.',
            ]
        );

        $parents = [
            [
                'name' => 'Демо Родитель Один',
                'email' => 'demo.parent.one@example.com',
                'phone' => '+7 (777) 100-00-01',
            ],
            [
                'name' => 'Демо Родитель Два',
                'email' => 'demo.parent.two@example.com',
                'phone' => '+7 (777) 100-00-02',
            ],
        ];

        $students = [
            [
                'full_name' => 'Демо Ученик Один',
                'age' => 11,
                'birth_date' => '2015-02-10',
                'gender' => 'male',
                'status' => 'active',
            ],
            [
                'full_name' => 'Демо Ученик Два',
                'age' => 12,
                'birth_date' => '2014-08-21',
                'gender' => 'female',
                'status' => 'active',
            ],
        ];

        foreach ($students as $index => $studentData) {
            $parent = User::updateOrCreate(
                ['email' => $parents[$index]['email']],
                [
                    ...$parents[$index],
                    'password' => Hash::make('parent123'),
                    'role' => 'parent',
                ]
            );

            $student = Student::updateOrCreate(
                ['full_name' => $studentData['full_name']],
                [
                    ...$studentData,
                    'parent_id' => $parent->id,
                    'current_course_id' => $course->id,
                ]
            );

            $group->students()->syncWithoutDetaching([
                $student->id => [
                    'enrolled_at' => now()->toDateString(),
                    'status' => 'active',
                ],
            ]);
        }

        $group->update([
            'course_id' => $course->id,
            'current_students' => $group->students()->wherePivot('status', 'active')->count(),
            'status' => 'active',
        ]);
    }
}
