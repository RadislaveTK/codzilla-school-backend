<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class GroupResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'name' => $this->name,
            'course_id' => $this->course_id,
            'max_students' => $this->max_students,
            'current_students' => $this->current_students,
            'available_places' => $this->available_places ?? ($this->max_students - $this->current_students),
            'is_full' => $this->is_full ?? ($this->current_students >= $this->max_students),
            'status' => $this->status,
            'status_text' => $this->status_text ?? $this->getStatusTextAttribute(),
            'description' => $this->description,
            'course' => $this->whenLoaded('course', function() {
                return new CourseResource($this->course);
            }),
            'students' => $this->whenLoaded('students', function() {
                return StudentResource::collection($this->students);
            }),
            'schedules' => $this->whenLoaded('schedules', function() {
                return ScheduleResource::collection($this->schedules);
            }),
            'students_count' => $this->students_count ?? $this->students()->count(),
        ]);
    }

    /**
     * Получить текст статуса
     */
    protected function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'forming' => 'Формируется',
            'active' => 'Идёт набор',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена',
            default => 'Неизвестно',
        };
    }

    /**
     * Создание коллекции ресурсов с дополнительными данными
     */
    public static function collection($resource)
    {
        // Проверяем, что ресурс не является MissingValue
        if (!$resource || $resource instanceof \Illuminate\Http\Resources\MissingValue) {
            return parent::collection(collect());
        }

        // Получаем коллекцию для подсчётов
        $collection = $resource instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? collect($resource->items())
            : $resource;

        return parent::collection($resource)->additional([
            'stats' => [
                'total_groups' => $resource instanceof \Illuminate\Pagination\LengthAwarePaginator
                    ? $resource->total()
                    : $collection->count(),
                'forming' => $collection->where('status', 'forming')->count(),
                'active' => $collection->where('status', 'active')->count(),
                'completed' => $collection->where('status', 'completed')->count(),
                'cancelled' => $collection->where('status', 'cancelled')->count(),
            ],
        ]);
    }
}
