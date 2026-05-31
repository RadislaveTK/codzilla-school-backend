<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StudentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            // Основные поля
            'full_name' => $this->full_name,
            'age' => $this->age,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'birth_date_formatted' => $this->birth_date?->format('d.m.Y'),
            'gender' => $this->gender,
            'status' => $this->status,
            'status_text' => $this->full_status,

            // Связи
            'parent_id' => $this->parent_id,
            'parent' => new UserResource($this->whenLoaded('parent')),

            'current_course_id' => $this->current_course_id,
            'current_course' => new CourseResource($this->whenLoaded('currentCourse')),

            // НОВОЕ: группы
            'groups' => $this->whenLoaded('groups', function () {
                return GroupResource::collection($this->groups);
            }),
            'current_group' => $this->whenLoaded('groups', function() {
                $currentGroup = $this->groups->firstWhere('pivot.status', 'active');
                return $currentGroup ? new GroupResource($currentGroup) : null;
            }),

            'group_membership' => $this->whenLoaded('pivotGroups', function () {
                return GroupStudentResource::collection($this->pivotGroups);
            }),

            // Прогресс
            'progress' => $this->whenLoaded('progress', function () {
                return ProgressResource::collection($this->progress);
            }),

            // Статистика посещаемости
            'attendance_stats' => $this->when($request->routeIs('students.show'), function() {
                return [
                    'total_lessons' => $this->attendances()->count(),
                    'present' => $this->attendances()->where('status', 'present')->count(),
                    'absent' => $this->attendances()->where('status', 'absent')->count(),
                    'late' => $this->attendances()->where('status', 'late')->count(),
                    'attendance_rate' => $this->getAttendanceRate(),
                ];
            }),

            // Текущий прогресс в группе
            'current_progress_percent' => $this->when($request->user(), function() {
                return $this->getCurrentProgressPercent();
            }),
        ]);
    }

    private function getAttendanceRate(): float
    {
        $total = $this->attendances()->count();
        $present = $this->attendances()->where('status', 'present')->count();

        if ($total === 0) return 0;

        return round(($present / $total) * 100, 2);
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
            'stats' => [
                'total_active' => $collection->where('status', 'active')->count(),
                'total_graduated' => $collection->where('status', 'graduated')->count(),
                'total_left' => $collection->where('status', 'left')->count(),
                'gender_ratio' => [
                    'male' => $collection->where('gender', 'male')->count(),
                    'female' => $collection->where('gender', 'female')->count(),
                ],
            ],
        ]);
    }
}
