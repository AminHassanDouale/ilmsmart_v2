<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Academic Years
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');         // "2024-2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        // Levels (Primary, Middle, Secondary, University)
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Grades (Grade 1, Grade 2 ... inside a level)
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Subjects (Math, Physics, Arabic...)
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('name_fr')->nullable();
            $table->string('name_en')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 20)->nullable();
            $table->integer('coefficient')->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('academic_years');
    }
};
