<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Resources\AttendanceResource;
use App\Http\Resources\ScheduleResource;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Progress;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController
{
    /**
     * Получить все группы для выбора при отметке
     */
    public function getGroups(Request $request)
    {
        $groups = Group::with(['course'])
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups->map(fn($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'course_name' => $group->course->name,
                'students_count' => $group->activeStudents()->count(),
            ])
        ]);
    }

    /**
     * Получить расписание группы для отметки
     */
    public function getGroupSchedules(Request $request, Group $group)
    {
        $schedules = $group->schedules()
            ->with(['lesson'])
            ->whereDate('start_time', '<=', now())
            ->orderBy('start_time', 'desc')
            ->paginate($request->get('per_page', 20));

        return ScheduleResource::collection($schedules);
    }

    /**
     * Получить конкретное занятие для отметки
     */
    public function getScheduleForMarking(Schedule $schedule)
    {
        $students = $schedule->getAvailableStudents();

        $existingAttendances = $schedule->attendances()
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        return response()->json([
            'success' => true,
            'data' => [
                'schedule' => [
                    'id' => $schedule->id,
                    'lesson_title' => $schedule->lesson->title,
                    'group_name' => $schedule->group?->name,
                    'start_time' => $schedule->start_time->format('d.m.Y H:i'),
                    'end_time' => $schedule->end_time->format('H:i'),
                    'can_mark' => $schedule->can_mark_attendance,
                ],
                'students' => $students->map(function($student) use ($existingAttendances) {
                    $attendance = $existingAttendances->get($student->id);
                    return [
                        'id' => $student->id,
                        'full_name' => $student->full_name,
                        'status' => $attendance?->status ?? 'not_marked',
                        'attendance_id' => $attendance?->id,
                        'reason' => $attendance?->reason,
                    ];
                }),
            ]
        ]);
    }

    /**
     * Отметить посещаемость
     */
    public function markAttendance(Request $request, Schedule $schedule)
    {
        $request->validate([
            'marks' => 'required|array',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.status' => 'required|in:present,absent,late',
            'marks.*.reason' => 'nullable|string|max:500',
        ]);

        $markedBy = Auth::id();
        $markedAt = now();

        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        foreach ($request->marks as $mark) {
            try {
                $attendance = Attendance::updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'student_id' => $mark['student_id'],
                    ],
                    [
                        'status' => $mark['status'],
                        'reason' => $mark['reason'] ?? null,
                        'marked_by' => $markedBy,
                        'marked_at' => $markedAt,
                    ]
                );

                if ($attendance->wasRecentlyCreated) {
                    $results['created']++;
                } else {
                    $results['updated']++;
                }

                // Обновляем прогресс ученика
                if (method_exists(Progress::class, 'recalculate')) {
                    Progress::recalculate($mark['student_id'], $schedule->lesson->course_id);
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'student_id' => $mark['student_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Посещаемость сохранена: создано {$results['created']}, обновлено {$results['updated']}",
            'results' => $results,
            'stats' => $schedule->stats,
        ]);
    }

    /**
     * Быстрая отметка (все присутствуют)
     */
    public function markAllPresent(Request $request, Schedule $schedule)
    {
        $students = $schedule->getAvailableStudents();
        $markedBy = Auth::id();

        $count = 0;
        foreach ($students as $student) {
            Attendance::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => 'present',
                    'reason' => null,
                    'marked_by' => $markedBy,
                    'marked_at' => now(),
                ]
            );
            $count++;

            // Обновляем прогресс
            if (method_exists(Progress::class, 'recalculate')) {
                Progress::recalculate($student->id, $schedule->lesson->course_id);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Все {$count} учеников отмечены как присутствующие",
            'stats' => $schedule->stats,
        ]);
    }

    /**
     * Получить историю посещаемости ученика
     */
    public function studentHistory(Student $student, Request $request)
    {
        $statsQuery = $student->attendances();
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'present' => (clone $statsQuery)->where('status', 'present')->count(),
            'absent' => (clone $statsQuery)->where('status', 'absent')->count(),
            'late' => (clone $statsQuery)->where('status', 'late')->count(),
        ];

        $attendances = $student->attendances()
            ->with(['schedule.lesson.course', 'schedule.group'])
            ->orderBy('marked_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return AttendanceResource::collection($attendances)->additional([
            'stats' => $stats,
        ]);
    }
}
