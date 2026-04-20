<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make subject_id, teacher_id, academic_year_id optional for Islamic / standalone courses
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->change();
            $table->foreignId('teacher_id')->nullable()->change();
            $table->foreignId('academic_year_id')->nullable()->change();
            // Add a "level" tag: beginner | intermediate | premium
            $table->string('level')->nullable()->after('type');
        });

        // Add meta JSON to lessons for Islamic API content references
        Schema::table('lessons', function (Blueprint $table) {
            $table->json('lesson_meta')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('level');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('lesson_meta');
        });
    }
};
