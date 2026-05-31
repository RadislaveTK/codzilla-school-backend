<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class GroupStudentResource extends BaseResource
{
    protected $table = 'group_student';

    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'group_id' => $this->group_id,
            'student_id' => $this->student_id,
            'enrolled_at' => $this->enrolled_at?->format('Y-m-d'),
            'enrolled_at_formatted' => $this->enrolled_at?->format('d.m.Y'),
            'left_at' => $this->left_at?->format('Y-m-d'),
            'left_at_formatted' => $this->left_at?->format('d.m.Y'),
            'status' => $this->status,
            'status_text' => $this->status_text,

            // Загруженные связи
            'group' => new GroupResource($this->whenLoaded('group')),
            'student' => new StudentResource($this->whenLoaded('student')),

            // Вычисляемые поля
            'is_active' => $this->status === 'active',
            'enrollment_duration_days' => $this->enrolled_at?->diffInDays(now()),
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
            'stats' => [
                'active_count' => $collection->where('status', 'active')->count(),
                'graduated_count' => $collection->where('status', 'graduated')->count(),
                'dropped_count' => $collection->where('status', 'dropped')->count(),
            ],
        ]);
    }
}
