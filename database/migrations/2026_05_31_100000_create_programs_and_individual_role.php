<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add 'individual' to users.role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','teacher','student','parent','tutor','accountant','manager','individual') NOT NULL");

        // 2. Programs (a curated bundle/curriculum of courses)
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->string('title_fr')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->enum('level', ['beginner','intermediate','premium','custom'])->default('beginner');
            $table->enum('type',  ['free','paid','subscription'])->default('free');
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('duration_weeks')->nullable();
            $table->integer('capacity')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('schedule_type', ['none','daily','weekly','custom'])->default('weekly');
            $table->boolean('is_islamic')->default(false);
            $table->enum('audience', ['students','individuals','both'])->default('both');
            $table->enum('status', ['draft','published','archived'])->default('draft');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Program-Courses pivot (which courses make up this program, in order)
        Schema::create('program_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->unique(['program_id','course_id']);
        });

        // 4. Program sessions (scheduled live meetups: weekly/daily)
        Schema::create('program_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('day_of_week', ['mon','tue','wed','thu','fri','sat','sun'])->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('specific_date')->nullable();
            $table->enum('recurrence', ['once','daily','weekly','biweekly','monthly'])->default('weekly');
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // 5. Program enrollments (user-based, supports students + individuals)
        Schema::create('program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->enum('status', ['active','completed','cancelled','pending'])->default('active');
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['program_id','user_id']);
        });

        // 6. Session attendance (who showed up to which session)
        Schema::create('program_session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->enum('status', ['present','absent','late','excused'])->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['program_session_id','user_id','session_date'], 'session_attendance_unique');
        });

        // 7. Subscription history (audit trail of every subscription event)
        Schema::create('subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('event', ['created','renewed','upgraded','downgraded','cancelled','expired','reactivated','refunded']);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('DZD');
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_histories');
        Schema::dropIfExists('program_session_attendances');
        Schema::dropIfExists('program_enrollments');
        Schema::dropIfExists('program_sessions');
        Schema::dropIfExists('program_courses');
        Schema::dropIfExists('programs');
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','teacher','student','parent','tutor','accountant','manager') NOT NULL");
    }
};
