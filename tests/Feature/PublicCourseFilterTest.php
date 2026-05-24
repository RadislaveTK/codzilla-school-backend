<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_course_show_returns_basic_skills(): void
    {
        Course::create([
            'name' => 'Python Starter',
            'icon' => 'programming',
            'slug' => 'python-starter',
            'age_from' => 10,
            'age_to' => 14,
            'description' => 'Python Starter course',
            'basic_skills' => ['Python basics', 'Loops', 'Functions'],
            'price' => 1000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/public/courses/python-starter');

        $response
            ->assertOk()
            ->assertJsonPath('data.slug', 'python-starter')
            ->assertJsonPath('data.basic_skills.0', 'Python basics')
            ->assertJsonPath('data.basic_skills.1', 'Loops')
            ->assertJsonPath('data.basic_skills.2', 'Functions');
    }

    public function test_public_courses_can_be_filtered_by_overlapping_age_group(): void
    {
        Course::create([
            'name' => 'Html Css',
            'icon' => 'programming',
            'slug' => 'html-css',
            'age_from' => 9,
            'age_to' => 13,
            'description' => 'Html Css course',
            'price' => 1000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);

        Course::create([
            'name' => 'Drones',
            'icon' => 'dron',
            'slug' => 'drones',
            'age_from' => 12,
            'age_to' => 17,
            'description' => 'Drones course',
            'price' => 1000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/public/courses?age_group=7-10');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'html-css');
    }

    public function test_public_courses_can_be_filtered_by_direction(): void
    {
        Course::create([
            'name' => 'Robotics',
            'icon' => 'robot',
            'slug' => 'robotics',
            'age_from' => 7,
            'age_to' => 10,
            'description' => 'Robotics course',
            'price' => 1000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);

        Course::create([
            'name' => 'Games',
            'icon' => 'pacman',
            'slug' => 'games',
            'age_from' => 7,
            'age_to' => 10,
            'description' => 'Games course',
            'price' => 1000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/public/courses?direction=robot');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.icon', 'robot')
            ->assertJsonPath('filters.directions.robot', 'Робототехника');
    }

    public function test_public_courses_can_be_filtered_by_icon_alias(): void
    {
        Course::create([
            'name' => 'Programming',
            'icon' => 'programming',
            'slug' => 'programming',
            'age_from' => 10,
            'age_to' => 14,
            'description' => 'Programming course',
            'price' => 1000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);

        Course::create([
            'name' => 'Drones',
            'icon' => 'dron',
            'slug' => 'drones',
            'age_from' => 10,
            'age_to' => 14,
            'description' => 'Drones course',
            'price' => 1000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/public/courses?icon=dron');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.icon', 'dron')
            ->assertJsonPath('filters.directions.dron', 'Дроны');
    }
}
