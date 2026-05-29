<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_applications', function (Blueprint $table) {
            $table->string('type')->default('course')->after('id');
            $table->string('course')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('course_applications')
            ->whereNull('course')
            ->update(['course' => '']);

        Schema::table('course_applications', function (Blueprint $table) {
            $table->string('course')->nullable(false)->change();
            $table->dropColumn('type');
        });
    }
};