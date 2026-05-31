<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_users(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $createResponse = $this->postJson('/api/v1/admin/users', [
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'phone' => '+77001112233',
            'role' => 'admin',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'teacher@example.com')
            ->assertJsonPath('data.role', 'admin');

        $userId = $createResponse->json('data.id');

        $this->putJson("/api/v1/admin/users/{$userId}", [
            'name' => 'Teacher Updated',
            'email' => 'teacher@example.com',
            'role' => 'admin',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Teacher Updated')
            ->assertJsonPath('data.is_active', false);

        $this->getJson('/api/v1/admin/users?per_page=100')
            ->assertOk()
            ->assertJsonFragment(['email' => 'teacher@example.com']);
    }

    public function test_admin_can_manage_group_lessons_with_materials(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $course = Course::create([
            'name' => 'Python Starter',
            'slug' => 'python-starter',
            'age_from' => 8,
            'age_to' => 14,
            'description' => 'Course description',
            'price' => 120000,
            'duration_weeks' => 12,
            'is_active' => true,
        ]);

        $group = Group::create([
            'name' => 'Python A',
            'course_id' => $course->id,
            'max_students' => 12,
            'status' => 'active',
        ]);

        $createResponse = $this->postJson('/api/v1/admin/lessons', [
            'group_id' => $group->id,
            'title' => 'Variables and types',
            'starts_at' => '2026-06-01T10:00',
            'ends_at' => '2026-06-01T11:30',
            'room' => '101',
            'description' => 'Lesson plan',
            'materials' => ['presentation.pdf', 'task.zip'],
            'homework' => 'Solve exercises 1-5',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.group_id', $group->id)
            ->assertJsonPath('data.title', 'Variables and types')
            ->assertJsonPath('data.materials.0', 'presentation.pdf')
            ->assertJsonPath('data.homework', 'Solve exercises 1-5');

        $scheduleId = $createResponse->json('data.id');
        $this->assertDatabaseHas('schedules', ['id' => $scheduleId, 'group_id' => $group->id]);

        $this->putJson("/api/v1/admin/lessons/{$scheduleId}", [
            'group_id' => $group->id,
            'title' => 'Updated variables',
            'starts_at' => '2026-06-02T10:00',
            'ends_at' => '2026-06-02T11:30',
            'room' => '102',
            'materials' => ['updated.pdf'],
            'homework' => 'Read chapter 2',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated variables')
            ->assertJsonPath('data.room', '102')
            ->assertJsonPath('data.materials.0', 'updated.pdf');

        $this->getJson('/api/v1/admin/lessons?per_page=100')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['title' => 'Updated variables']);

        $this->deleteJson("/api/v1/admin/lessons/{$scheduleId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('schedules', ['id' => $scheduleId]);
        $this->assertSame(0, Schedule::count());
    }

    public function test_admin_can_open_group_with_students_and_schedules(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $parent = User::factory()->create(['role' => 'parent']);
        $course = Course::create([
            'name' => 'Scratch',
            'slug' => 'scratch',
            'age_from' => 7,
            'age_to' => 12,
            'description' => 'Course description',
            'price' => 90000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);
        $group = Group::create([
            'name' => 'Scratch A',
            'course_id' => $course->id,
            'max_students' => 12,
            'current_students' => 1,
            'status' => 'active',
        ]);
        $student = Student::create([
            'full_name' => 'Student One',
            'age' => 9,
            'gender' => 'male',
            'status' => 'active',
            'parent_id' => $parent->id,
            'current_course_id' => $course->id,
        ]);
        $group->students()->attach($student->id, [
            'enrolled_at' => now()->toDateString(),
            'status' => 'active',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'First lesson',
            'order' => 1,
            'materials' => ['intro.pdf'],
        ]);
        Schedule::create([
            'lesson_id' => $lesson->id,
            'group_id' => $group->id,
            'start_time' => '2026-06-01 10:00:00',
            'end_time' => '2026-06-01 11:30:00',
            'room' => '101',
        ]);

        $this->getJson("/api/v1/admin/groups/{$group->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Scratch A')
            ->assertJsonPath('data.students.0.full_name', 'Student One')
            ->assertJsonPath('data.schedules.0.lesson.title', 'First lesson')
            ->assertJsonPath('data.schedules.0.start_time', '2026-06-01T10:00')
            ->assertJsonPath('data.schedules.0.end_time', '2026-06-01T11:30');
    }

    public function test_admin_can_create_student_and_attach_to_group(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $parent = User::factory()->create(['role' => 'parent']);
        $course = Course::create([
            'name' => 'Robotics',
            'slug' => 'robotics',
            'age_from' => 9,
            'age_to' => 15,
            'description' => 'Course description',
            'price' => 150000,
            'duration_weeks' => 10,
            'is_active' => true,
        ]);
        $group = Group::create([
            'name' => 'Robotics A',
            'course_id' => $course->id,
            'max_students' => 10,
            'current_students' => 0,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/admin/students', [
            'full_name' => 'New Student',
            'age' => 11,
            'gender' => 'female',
            'status' => 'active',
            'parent_id' => $parent->id,
            'current_course_id' => $course->id,
            'group_id' => $group->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'New Student')
            ->assertJsonPath('data.groups.0.id', $group->id);

        $studentId = $response->json('data.id');

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'parent_id' => $parent->id,
            'current_course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('group_student', [
            'group_id' => $group->id,
            'student_id' => $studentId,
            'status' => 'active',
        ]);
        $this->assertSame(1, $group->refresh()->current_students);
    }

    public function test_attendance_history_stats_count_absent_for_admin_and_parent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = User::factory()->create(['role' => 'parent']);
        $course = Course::create([
            'name' => 'Game Dev',
            'slug' => 'game-dev',
            'age_from' => 10,
            'age_to' => 15,
            'description' => 'Course description',
            'price' => 100000,
            'duration_weeks' => 8,
            'is_active' => true,
        ]);
        $group = Group::create([
            'name' => 'Game Dev A',
            'course_id' => $course->id,
            'max_students' => 10,
            'current_students' => 1,
            'status' => 'active',
        ]);
        $student = Student::create([
            'full_name' => 'Demo Student',
            'age' => 12,
            'gender' => 'male',
            'status' => 'active',
            'parent_id' => $parent->id,
            'current_course_id' => $course->id,
        ]);
        $group->students()->attach($student->id, [
            'enrolled_at' => now()->toDateString(),
            'status' => 'active',
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Intro',
            'order' => 1,
        ]);
        $schedule = Schedule::create([
            'lesson_id' => $lesson->id,
            'group_id' => $group->id,
            'start_time' => '2026-06-01 10:00:00',
            'end_time' => '2026-06-01 11:30:00',
            'room' => '101',
        ]);
        Attendance::create([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'status' => 'absent',
            'marked_by' => $admin->id,
            'marked_at' => now(),
        ]);

        Sanctum::actingAs($admin);
        $this->getJson("/api/v1/admin/attendance/student/{$student->id}")
            ->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.present', 0)
            ->assertJsonPath('stats.absent', 1)
            ->assertJsonPath('stats.late', 0);

        Sanctum::actingAs($parent);
        $this->getJson("/api/v1/parent/children/{$student->id}/attendance")
            ->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.present', 0)
            ->assertJsonPath('stats.absent', 1)
            ->assertJsonPath('stats.late', 0);
    }
}

