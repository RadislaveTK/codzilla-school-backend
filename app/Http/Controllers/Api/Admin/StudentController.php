<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Resources\StudentResource;
use App\Models\Group;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController
{
    public function index(Request $request)
    {
        $query = Student::with(['parent', 'currentCourse', 'groups.course'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('group_id')) {
            $query->whereHas('groups', fn ($groupQuery) =>
                $groupQuery->where('groups.id', $request->group_id)
            );
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        return StudentResource::collection($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:3', 'max:18'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'status' => ['nullable', Rule::in(['active', 'graduated', 'left'])],
            'parent_id' => ['required', 'exists:users,id'],
            'current_course_id' => ['nullable', 'exists:courses,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
        ]);

        $student = DB::transaction(function () use ($validated) {
            $group = isset($validated['group_id'])
                ? Group::findOrFail($validated['group_id'])
                : null;

            $student = Student::create([
                'full_name' => $validated['full_name'],
                'age' => $validated['age'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'parent_id' => $validated['parent_id'],
                'current_course_id' => $validated['current_course_id'] ?? $group?->course_id,
            ]);

            if ($group) {
                $group->students()->attach($student->id, [
                    'enrolled_at' => now()->toDateString(),
                    'status' => 'active',
                ]);
                $group->increment('current_students');
            }

            return $student;
        });

        $student->load(['parent', 'currentCourse', 'groups.course']);

        return response()->json([
            'success' => true,
            'message' => 'Ученик создан',
            'data' => new StudentResource($student),
        ], 201);
    }

    public function show(Student $student)
    {
        $student->load(['parent', 'currentCourse', 'groups.course', 'progress']);

        return new StudentResource($student);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:3', 'max:18'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'status' => ['nullable', Rule::in(['active', 'graduated', 'left'])],
            'parent_id' => ['sometimes', 'required', 'exists:users,id'],
            'current_course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $student->update($validated);
        $student->load(['parent', 'currentCourse', 'groups.course']);

        return response()->json([
            'success' => true,
            'message' => 'Ученик обновлен',
            'data' => new StudentResource($student),
        ]);
    }

    public function destroy(Student $student)
    {
        if ($student->attendances()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить ученика с историей посещаемости',
            ], 422);
        }

        $student->groups()->detach();
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ученик удален',
        ]);
    }
}
