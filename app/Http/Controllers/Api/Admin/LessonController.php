<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonController
{
    public function index(Request $request)
    {
        $query = Schedule::with(['lesson.course', 'group.course'])
            ->withCount('attendances')
            ->orderBy('start_time', 'desc');

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        $schedules = $query
            ->paginate($request->get('per_page', 20))
            ->through(fn (Schedule $schedule) => $this->schedulePayload($schedule));

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'materials' => ['nullable', 'array'],
            'materials.*' => ['required', 'string', 'max:1000'],
            'homework' => ['nullable', 'string'],
        ]);

        $schedule = DB::transaction(function () use ($validated) {
            $group = Group::findOrFail($validated['group_id']);
            $nextOrder = Lesson::where('course_id', $group->course_id)->max('order') + 1;

            $lesson = Lesson::create([
                'course_id' => $group->course_id,
                'title' => $validated['title'],
                'order' => $nextOrder,
                'description' => $validated['description'] ?? null,
                'materials' => $validated['materials'] ?? [],
                'homework' => $validated['homework'] ?? null,
            ]);

            return Schedule::create([
                'lesson_id' => $lesson->id,
                'group_id' => $group->id,
                'start_time' => $validated['starts_at'],
                'end_time' => $validated['ends_at'] ?? Carbon::parse($validated['starts_at'])->addMinutes(90),
                'room' => $validated['room'] ?? null,
            ]);
        });

        $schedule->load(['lesson.course', 'group.course']);

        return response()->json([
            'success' => true,
            'message' => 'Занятие создано',
            'data' => $this->schedulePayload($schedule),
        ], 201);
    }

    public function show(Schedule $schedule)
    {
        $schedule->load(['lesson.course', 'group.course']);

        return response()->json([
            'success' => true,
            'data' => $this->schedulePayload($schedule),
        ]);
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'group_id' => ['sometimes', 'required', 'exists:groups,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'materials' => ['nullable', 'array'],
            'materials.*' => ['required', 'string', 'max:1000'],
            'homework' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $schedule) {
            $lesson = $schedule->lesson;
            $group = isset($validated['group_id'])
                ? Group::findOrFail($validated['group_id'])
                : $schedule->group;

            $lessonPayload = [];

            if (isset($validated['title'])) {
                $lessonPayload['title'] = $validated['title'];
            }

            foreach (['description', 'materials', 'homework'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $lessonPayload[$field] = $validated[$field];
                }
            }

            if ($group && $lesson->course_id !== $group->course_id) {
                $lessonPayload['course_id'] = $group->course_id;
                $lessonPayload['order'] = Lesson::where('course_id', $group->course_id)->max('order') + 1;
            }

            if ($lessonPayload) {
                $lesson->update($lessonPayload);
            }

            $schedulePayload = [];

            if ($group) {
                $schedulePayload['group_id'] = $group->id;
            }

            if (isset($validated['starts_at'])) {
                $schedulePayload['start_time'] = $validated['starts_at'];
            }

            if (array_key_exists('ends_at', $validated)) {
                $schedulePayload['end_time'] = $validated['ends_at'];
            }

            if (array_key_exists('room', $validated)) {
                $schedulePayload['room'] = $validated['room'];
            }

            if ($schedulePayload) {
                $schedule->update($schedulePayload);
            }
        });

        $schedule->refresh()->load(['lesson.course', 'group.course']);

        return response()->json([
            'success' => true,
            'message' => 'Занятие обновлено',
            'data' => $this->schedulePayload($schedule),
        ]);
    }

    public function destroy(Schedule $schedule)
    {
        if ($schedule->attendances()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить занятие с отметками посещаемости',
            ], 422);
        }

        $lesson = $schedule->lesson;
        $schedule->delete();

        if ($lesson && !$lesson->schedules()->exists()) {
            $lesson->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Занятие удалено',
        ]);
    }

    private function schedulePayload(Schedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'lesson_id' => $schedule->lesson_id,
            'group_id' => $schedule->group_id,
            'course_id' => $schedule->lesson?->course_id,
            'title' => $schedule->lesson?->title,
            'description' => $schedule->lesson?->description,
            'materials' => $schedule->lesson?->materials ?? [],
            'homework' => $schedule->lesson?->homework,
            'starts_at' => $schedule->start_time?->format('Y-m-d\TH:i'),
            'ends_at' => $schedule->end_time?->format('Y-m-d\TH:i'),
            'date_formatted' => $schedule->start_time?->format('d.m.Y'),
            'time_range' => $schedule->start_time?->format('H:i') . ' - ' . $schedule->end_time?->format('H:i'),
            'room' => $schedule->room,
            'attendances_count' => $schedule->attendances_count,
            'lesson' => $schedule->lesson,
            'group' => $schedule->group,
        ];
    }
}
