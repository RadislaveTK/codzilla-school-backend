<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'name',
        'icon',           // НОВОЕ ПОЛЕ: robot, dron, programming, pacman
        'slug',
        'age_from',
        'age_to',
        'description',
        'basic_skills',
        'price',
        'duration_weeks',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'age_from' => 'integer',
        'age_to' => 'integer',
        'basic_skills' => 'array',
    ];

    // Связи
    public function students()
    {
        return $this->belongsToMany(Student::class, 'course_student')
            ->withPivot('enrolled_at', 'graduated_at', 'status')
            ->withTimestamps();
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function activeStudents()
    {
        return $this->students()->wherePivot('status', 'enrolled');
    }

    // Аттрибуты
    public function getAgeRangeAttribute(): string
    {
        return "{$this->age_from}-{$this->age_to} лет";
    }

     /**
     * Получить имя файла иконки
     */
    public function getIconFileAttribute(): string
    {
        return match($this->icon) {
            'robot' => 'robot.svg',
            'dron' => 'dron.svg',
            'pacman' => 'pacman.svg',
            default => 'programming.svg',
        };
    }

    /**
     * Получить полный URL иконки
     */
    public function getIconUrlAttribute(): string
    {
        return "/icons/courses/{$this->icon_file}";
    }

    /**
     * Получить читаемое название иконки
     */
    public function getIconLabelAttribute(): string
    {
        return match($this->icon) {
            'robot' => 'Робототехника',
            'dron' => 'Дроны',
            'pacman' => 'Игры',
            default => 'Программирование',
        };
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' ₸';
    }
}
