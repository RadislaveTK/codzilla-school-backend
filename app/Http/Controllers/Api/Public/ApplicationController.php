<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\CourseApplication;
use App\Services\CourseApplicationNotifier;
use Illuminate\Http\Request;

class ApplicationController
{
    public function store(Request $request, CourseApplicationNotifier $notifier)
    {
        return $this->storeByType($request, $notifier, CourseApplication::TYPE_COURSE);
    }

    public function feedback(Request $request, CourseApplicationNotifier $notifier)
    {
        return $this->storeByType($request, $notifier, CourseApplication::TYPE_FEEDBACK);
    }

    private function storeByType(Request $request, CourseApplicationNotifier $notifier, string $type)
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'message' => 'nullable|string|max:5000',
        ];

        if ($type === CourseApplication::TYPE_COURSE) {
            $rules['course'] = 'required|string|max:255';
        }

        if ($type === CourseApplication::TYPE_FEEDBACK) {
            $rules['questions'] = 'nullable|string|max:5000';
        }

        $validated = $request->validate($rules);
        $validated['type'] = $type;

        if ($type === CourseApplication::TYPE_FEEDBACK && isset($validated['questions'])) {
            $validated['message'] = $validated['questions'];
            unset($validated['questions']);
        }

        $application = CourseApplication::create($validated);

        $notifier->notify($application);

        return response()->json([
            'success' => true,
            'message' => 'Заявка успешно отправлена',
            'application_id' => $application->id,
            'type' => $application->type,
        ], 201);
    }
}