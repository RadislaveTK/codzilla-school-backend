<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CourseResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            // Основные поля
            'name' => $this->name,
            'icon' => $this->icon,                      // robot, dron, programming, pacman
            'icon_file' => $this->icon_file,            // robot.svg
            'icon_url' => $this->icon_url,              // /icons/courses/robot.svg
            'icon_label' => $this->icon_label,          // Робототехника
            'slug' => $this->slug,
            'age_from' => $this->age_from,
            'age_to' => $this->age_to,
            'age_range' => $this->age_range,
            'description' => $this->description,
            'basic_skills' => $this->basic_skills ?? [],
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'duration_weeks' => $this->duration_weeks,
            'is_active' => $this->is_active,

            // НОВОЕ: группы курса
            'groups' => $this->whenLoaded('groups', function () {
                return GroupResource::collection($this->groups);
            }),
            'active_groups' => $this->whenLoaded('groups', function () {
                return GroupResource::collection(
                    $this->groups->where('status', 'active')
                );
            }),

            // Уроки
            'lessons' => $this->whenLoaded('lessons', function () {
                return LessonResource::collection($this->lessons);
            }),

            // Статистика
            'students_count' => $this->whenCounted('students'),
            'groups_count' => $this->whenCounted('groups'),
            'active_groups_count' => $this->whenLoaded('groups', function () {
                return $this->groups->where('status', 'active')->count();
            }),

            // Данные из pivot
            'pivot_enrolled_at' => $this->whenPivotLoaded('course_student', function () {
                return $this->pivot->enrolled_at?->format('d.m.Y');
            }),
        ]);
    }

    public static function collection($resource)
    {
        if (!$resource || $resource instanceof \Illuminate\Http\Resources\MissingValue) {
            return parent::collection(collect());
        }

        $collection = method_exists($resource, 'items')
            ? collect($resource->items())
            : collect($resource);

        return parent::collection($resource)->additional([
            'filters' => [
                'age_groups' => [
                    '3-6' => 'Дошкольники',
                    '7-10' => 'Младшая школа',
                    '11-14' => 'Средняя школа',
                    '15-18' => 'Старшая школа',
                ],
                'directions' => [
                    'pacman' => 'Игры',
                    'robot' => 'Робототехника',
                    'dron' => 'Дроны',
                    'programming' => 'Программирование',
                ],
            ],
            'stats' => [
                'total_courses' => method_exists($resource, 'total') ? $resource->total() : $resource->count(),
                'active_courses' => $collection->where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * Список доступных иконок для выбора (для фронтенда)
     */
    public static function getAvailableIcons(): array
    {
        return [
            ['value' => 'robot', 'label' => 'Робототехника', 'file' => 'robot.svg'],
            ['value' => 'dron', 'label' => 'Дроны', 'file' => 'dron.svg'],
            ['value' => 'programming', 'label' => 'Программирование', 'file' => 'programming.svg'],
            ['value' => 'pacman', 'label' => 'Игры', 'file' => 'pacman.svg'],
        ];
    }
}
