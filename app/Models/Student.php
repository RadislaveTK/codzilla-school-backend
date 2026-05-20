<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Student extends Model
{
    protected $fillable = [
        'full_name', 'age', 'birth_date', 'gender', 'status', 'parent_id', 'current_course_id'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'age' => 'integer',
    ];

    // ========== СВЯЗИ ==========

    // Связь с родителем
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    // Текущий курс (быстрый доступ)
    public function currentCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'current_course_id');
    }

    // Все курсы (история) — через группы
    public function coursesViaGroups(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'groups', 'id', 'course_id')
            ->via('groups');
    }

    // Группы, в которых состоит/состоял ученик
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_student')
                    ->withPivot('enrolled_at', 'left_at', 'status')
                    ->withTimestamps();
    }

    // Текущая активная группа
    public function currentGroup()
    {
        return $this->groups()
                    ->wherePivot('status', 'active')
                    ->latest('pivot_enrolled_at')
                    ->first();
    }

    // Все записи о посещаемости
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // Прогресс по курсам
    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class);
    }

    // ========== ПОЛУЧЕНИЕ РАСПИСАНИЯ ЧЕРЕЗ ГРУППУ ==========

    // Вариант 1: Через hasManyThrough (элегантно)
    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(
            Schedule::class,           // Какая модель нужна
            GroupStudent::class,       // Через какую модель идём
            'student_id',              // Ключ в GroupStudent, ссылающийся на Student
            'group_id',                // Ключ в Schedule, ссылающийся на Group
            'id',                      // Локальный ключ в Student
            'group_id'                 // Локальный ключ в GroupStudent, который связывается с Schedule
        );
    }

    // Вариант 2: Проще для понимания (через метод)
    public function getSchedulesAttribute()
    {
        $group = $this->currentGroup();
        if (!$group) {
            return collect();
        }
        return $group->schedules;
    }

    // ========== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ==========

    // Получить прогресс по конкретному курсу
    public function getProgressForCourse(Course $course): ?Progress
    {
        return $this->progress()->where('course_id', $course->id)->first();
    }

    // Получить общий процент прогресса по текущей группе
    public function getCurrentProgressPercent(): float
    {
        $group = $this->currentGroup();
        if (!$group || !$group->course) {
            return 0;
        }

        $totalLessons = $group->course->lessons()->count();
        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = $this->attendances()
            ->whereHas('schedule', function ($q) use ($group) {
                $q->where('group_id', $group->id);
            })
            ->where('status', 'present')
            ->count();

        return round(($completedLessons / $totalLessons) * 100, 2);
    }

    // Статистика посещаемости по текущей группе
    public function getAttendanceStatsForCurrentGroup(): array
    {
        $group = $this->currentGroup();
        if (!$group) {
            return ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
        }

        $attendances = $this->attendances()
            ->whereHas('schedule', fn($q) => $q->where('group_id', $group->id))
            ->get();

        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
        ];
    }
}
