<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController
{
    public function index(Request $request)
    {
        $courses = Course::withCount(['students', 'groups'])
            ->paginate($request->get('per_page', 20));

        return CourseResource::collection($courses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|in:robot,dron,programming,pacman',
            'age_from' => 'required|integer|min:3',
            'age_to' => 'required|integer|gte:age_from',
            'description' => 'required|string',
            'basic_skills' => 'nullable|array',
            'basic_skills.*' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_weeks' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();

        // Устанавливаем иконку по умолчанию, если не выбрана
        if (empty($validated['icon'])) {
            $validated['icon'] = 'programming';
        }

        $course = Course::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Курс создан',
            'data' => new CourseResource($course)
        ], 201);
    }

    public function show(Course $course)
    {
        $course->load(['lessons', 'groups']);

        return new CourseResource($course);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'icon' => 'nullable|string|in:robot,dron,programming,pacman',
            'age_from' => 'integer|min:3',
            'age_to' => 'integer|gte:age_from',
            'description' => 'string',
            'basic_skills' => 'nullable|array',
            'basic_skills.*' => 'required|string|max:255',
            'price' => 'numeric|min:0',
            'duration_weeks' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $course->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Курс обновлён',
            'data' => new CourseResource($course)
        ]);
    }

    public function destroy(Course $course)
    {
        if ($course->groups()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить курс с группами'
            ], 422);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Курс удалён'
        ]);
    }

    /**
     * Получить список доступных иконок для фронтенда
     */
    public function getIcons()
    {
        return response()->json([
            'success' => true,
            'data' => CourseResource::getAvailableIcons(),
        ]);
    }
}
