<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseApplication extends Model
{
    public const TYPE_COURSE = 'course';
    public const TYPE_FEEDBACK = 'feedback';

    protected $fillable = [
        'type',
        'full_name',
        'phone',
        'course',
        'message',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];
}