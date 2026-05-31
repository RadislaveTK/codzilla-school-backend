<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class LessonResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'course_id' => $this->course_id,
            'title' => $this->title,
            'order' => $this->order,
            'description' => $this->description,
            'materials' => $this->materials ?? [],
            'homework' => $this->homework,
            'course' => new CourseResource($this->whenLoaded('course')),
        ]);
    }
}
