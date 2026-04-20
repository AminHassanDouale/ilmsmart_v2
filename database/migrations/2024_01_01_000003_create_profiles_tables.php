<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Student profiles
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_number')->unique();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('school_name')->nullable();
            $table->text('address')->nullable();
            $table->string('nationality')->nullable();
            $table->enum('student_type', ['school', 'individual'])->default('school');
            $table->timestamps();
        });

        // Parent profiles
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('national_id')->nullable();
            $table->timestamps();
        });

        // Parent-Student pivot
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('relationship', ['father', 'mother', 'guardian'])->default('father');
            $table->timestamps();
        });

        // Teacher profiles
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->string('qualification')->nullable();
            $table->integer('experience_years')->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_tutor')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // Teacher subjects pivot
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teacher_id', 'subject_id']);
        });

        // Teacher availability
        Schema::create('teacher_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->enum('day', ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_availability');
        Schema::dropIfExists('teacher_subjects');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('parents');
        Schema::dropIfExists('students');
    }
};
