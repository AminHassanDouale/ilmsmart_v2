<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Courses
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->string('title_fr')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->string('thumbnail')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->enum('type', ['free', 'paid', 'subscription'])->default('free');
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('duration_hours')->default(0);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Modules (chapters inside a course)
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->string('title_fr')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Lessons
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->string('title_fr')->nullable();
            $table->string('title_en')->nullable();
            $table->text('content')->nullable();
            $table->enum('type', ['video', 'document', 'quiz', 'assignment', 'live', 'text'])->default('video');
            $table->string('video_url')->nullable();
            $table->string('video_provider')->nullable(); // youtube, vimeo, cloudflare, upload
            $table->integer('duration_minutes')->default(0);
            $table->boolean('is_free_preview')->default(false);
            $table->integer('order')->default(0);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        // Lesson attachments
        Schema::create('lesson_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Student enrollment in courses
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            $table->unique(['student_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('lesson_documents');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('courses');
    }
};
