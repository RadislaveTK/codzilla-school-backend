<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController
{
    // Список всех активных курсов
    public function index(Request $request)
    {
        $query = Course::where('is_active', true)
            ->withCount('students');

        if ($request->filled('age_group') && preg_match('/^(\d+)-(\d+)$/', $request->age_group, $matches)) {
            $query->where('age_from', '<=', (int) $matches[2])
                ->where('age_to', '>=', (int) $matches[1]);
        }

        $direction = $request->query('direction', $request->query('icon'));

        if (in_array($direction, ['robot', 'dron', 'programming', 'pacman'], true)) {
            $query->where('icon', $direction);
        }

        $courses = $query->paginate($request->get('per_page', 12));

        return CourseResource::collection($courses);
    }

    // Детальная страница курса
    public function show($slug)
    {
        $course = Course::where('slug', $slug)
            ->where('is_active', true)
            ->with(['lessons', 'groups' => function($q) {
                $q->where('status', 'active')->withCount('students');
            }])
            ->firstOrFail();

        return new CourseResource($course);
    }

    // Программа курса (уроки)
    public function lessons($slug)
    {
        $course = Course::where('slug', $slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'course' => $course->name,
            'lessons' => $course->lessons()->orderBy('order')->get(),
        ]);
    }
}
