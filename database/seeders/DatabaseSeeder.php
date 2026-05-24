<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            CoursesTableSeeder::class,
            StudentsTableSeeder::class,
            GroupsTableSeeder::class,
            DemoGroupSeeder::class,
            LessonsTableSeeder::class,
            SchedulesTableSeeder::class,
        ]);
    }
}
