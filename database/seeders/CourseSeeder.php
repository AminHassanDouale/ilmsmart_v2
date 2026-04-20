<?php

namespace Database\Seeders;

use App\Models\{Course, Module, Lesson, Teacher, Subject, AcademicYear, Enrollment, Student};
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = Teacher::first();
        $year    = AcademicYear::where('is_current', true)->first();
        $subject = Subject::where('name', 'Mathématiques')->first();
        $student = Student::first();

        if (!$teacher || !$year || !$subject) return;

        // Create a sample course
        $course = Course::create([
            'subject_id'       => $subject->id,
            'teacher_id'       => $teacher->id,
            'academic_year_id' => $year->id,
            'title'            => 'Mathématiques - 3ème AM',
            'title_ar'         => 'رياضيات - السنة الثالثة متوسط',
            'title_fr'         => 'Mathématiques - 3ème AM',
            'title_en'         => 'Mathematics - 9th Grade',
            'description'      => 'Cours complet de mathématiques pour la 3ème année moyenne.',
            'description_ar'   => 'دورة رياضيات شاملة للسنة الثالثة متوسط.',
            'description_fr'   => 'Cours complet de mathématiques pour la 3ème année moyenne.',
            'description_en'   => 'Complete mathematics course for 9th grade.',
            'status'           => 'published',
            'type'             => 'free',
            'price'            => 0,
            'duration_hours'   => 40,
        ]);

        // Modules
        $modules = [
            ['title' => 'Chapitre 1: Nombres entiers',        'title_ar' => 'الفصل 1: الأعداد الصحيحة'],
            ['title' => 'Chapitre 2: Fractions',              'title_ar' => 'الفصل 2: الكسور'],
            ['title' => 'Chapitre 3: Équations du 1er degré', 'title_ar' => 'الفصل 3: معادلات الدرجة الأولى'],
        ];

        foreach ($modules as $order => $moduleData) {
            $module = Module::create([
                'course_id' => $course->id,
                'title'     => $moduleData['title'],
                'title_ar'  => $moduleData['title_ar'],
                'title_fr'  => $moduleData['title'],
                'title_en'  => $moduleData['title'],
                'order'     => $order + 1,
            ]);

            // Lessons per module
            for ($i = 1; $i <= 3; $i++) {
                Lesson::create([
                    'module_id'        => $module->id,
                    'title'            => "Leçon {$i}: " . $moduleData['title'],
                    'title_ar'         => "الدرس {$i}: " . $moduleData['title_ar'],
                    'title_fr'         => "Leçon {$i}: " . $moduleData['title'],
                    'type'             => $i === 1 ? 'video' : ($i === 2 ? 'document' : 'quiz'),
                    'content'          => 'Contenu de la leçon...',
                    'duration_minutes' => 30,
                    'is_free_preview'  => $i === 1,
                    'order'            => $i,
                    'status'           => 'published',
                ]);
            }
        }

        // Enroll sample student
        if ($student) {
            Enrollment::create([
                'student_id'       => $student->id,
                'course_id'        => $course->id,
                'enrolled_at'      => now(),
                'progress_percent' => 33,
                'status'           => 'active',
            ]);
        }
    }
}
