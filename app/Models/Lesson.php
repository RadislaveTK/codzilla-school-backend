<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'order',
        'description',
        'materials',
        'homework',
    ];

    protected $casts = [
        'materials' => 'array',
        'order' => 'integer',
    ];

    // Связи
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    // Получить расписание на сегодня
    public function todaySchedule()
    {
        return $this->schedules()
            ->whereDate('start_time', today())
            ->first();
    }
}
