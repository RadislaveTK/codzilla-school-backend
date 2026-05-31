<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ScheduleResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            // Основные поля
            'lesson_id' => $this->lesson_id,
            'group_id' => $this->group_id, // НОВОЕ
            'start_time' => $this->start_time?->format('Y-m-d\TH:i'),
            'end_time' => $this->end_time?->format('Y-m-d\TH:i'),
            'start_time_formatted' => $this->start_time?->format('d.m.Y H:i'),
            'end_time_formatted' => $this->end_time?->format('H:i'),
            'date_formatted' => $this->start_time?->format('d.m.Y'),
            'time_range' => $this->start_time?->format('H:i') . ' - ' . $this->end_time?->format('H:i'),
            'room' => $this->room,

            // Связи
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'group' => new GroupResource($this->whenLoaded('group')),
            'attendances' => $this->whenLoaded('attendances', function () {
                return AttendanceResource::collection($this->attendances);
            }),

            // Вычисляемые поля
            'title' => $this->title, // из мутатора
            'is_past' => $this->is_past,
            'is_today' => $this->is_today,
            'is_future' => $this->start_time?->isFuture(),
            'can_mark_attendance' => $this->can_mark_attendance,

            // Статистика (только если нужна)
            'stats' => $this->when($request->routeIs('attendance.show'), function() {
                return $this->stats;
            }),

            // Количество отметок
            'attendances_count' => $this->whenCounted('attendances'),

            // Для администратора
            'students_count_in_group' => $this->when($request->user()?->isAdmin(), function() {
                return $this->group?->activeStudents()->count() ?? 0;
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
            'grouped_by_date' => true,
            'upcoming_count' => $collection->filter(fn($schedule) => $schedule->start_time?->isFuture())->count(),
            'past_count' => $collection->filter(fn($schedule) => $schedule->start_time?->isPast())->count(),
        ]);
    }
}
