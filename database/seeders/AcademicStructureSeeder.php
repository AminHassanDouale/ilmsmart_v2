<?php

namespace Database\Seeders;

use App\Models\{AcademicYear, Level, Grade, Subject};
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        // Academic Year
        $year = AcademicYear::create([
            'name'       => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date'   => '2025-06-30',
            'is_current' => true,
        ]);

        // Levels
        $levels = [
            ['name' => 'Primaire',   'name_ar' => 'الابتدائي',  'name_fr' => 'Primaire',   'name_en' => 'Primary',    'order' => 1],
            ['name' => 'Moyen',      'name_ar' => 'المتوسط',    'name_fr' => 'Moyen',      'name_en' => 'Middle',     'order' => 2],
            ['name' => 'Secondaire', 'name_ar' => 'الثانوي',    'name_fr' => 'Secondaire', 'name_en' => 'Secondary',  'order' => 3],
        ];

        foreach ($levels as $levelData) {
            $level = Level::create($levelData);

            // Grades per level
            $gradesData = match($level->name) {
                'Primaire'   => [
                    ['name' => '1ère AP', 'name_ar' => 'السنة 1 ابتدائي', 'name_fr' => '1ère AP', 'name_en' => 'Grade 1', 'order' => 1],
                    ['name' => '2ème AP', 'name_ar' => 'السنة 2 ابتدائي', 'name_fr' => '2ème AP', 'name_en' => 'Grade 2', 'order' => 2],
                    ['name' => '3ème AP', 'name_ar' => 'السنة 3 ابتدائي', 'name_fr' => '3ème AP', 'name_en' => 'Grade 3', 'order' => 3],
                    ['name' => '4ème AP', 'name_ar' => 'السنة 4 ابتدائي', 'name_fr' => '4ème AP', 'name_en' => 'Grade 4', 'order' => 4],
                    ['name' => '5ème AP', 'name_ar' => 'السنة 5 ابتدائي', 'name_fr' => '5ème AP', 'name_en' => 'Grade 5', 'order' => 5],
                ],
                'Moyen' => [
                    ['name' => '1ère AM', 'name_ar' => 'السنة 1 متوسط', 'name_fr' => '1ère AM', 'name_en' => 'Grade 6',  'order' => 1],
                    ['name' => '2ème AM', 'name_ar' => 'السنة 2 متوسط', 'name_fr' => '2ème AM', 'name_en' => 'Grade 7',  'order' => 2],
                    ['name' => '3ème AM', 'name_ar' => 'السنة 3 متوسط', 'name_fr' => '3ème AM', 'name_en' => 'Grade 8',  'order' => 3],
                    ['name' => '4ème AM', 'name_ar' => 'السنة 4 متوسط', 'name_fr' => '4ème AM', 'name_en' => 'Grade 9',  'order' => 4],
                ],
                'Secondaire' => [
                    ['name' => '1ère AS', 'name_ar' => 'السنة 1 ثانوي', 'name_fr' => '1ère AS', 'name_en' => 'Grade 10', 'order' => 1],
                    ['name' => '2ème AS', 'name_ar' => 'السنة 2 ثانوي', 'name_fr' => '2ème AS', 'name_en' => 'Grade 11', 'order' => 2],
                    ['name' => '3ème AS', 'name_ar' => 'السنة 3 ثانوي', 'name_fr' => '3ème AS', 'name_en' => 'Grade 12', 'order' => 3],
                ],
                default => [],
            };

            foreach ($gradesData as $gradeData) {
                $grade = Grade::create(array_merge($gradeData, ['level_id' => $level->id]));

                // Subjects per grade (common subjects)
                $subjects = [
                    ['name' => 'Mathématiques', 'name_ar' => 'الرياضيات',    'name_fr' => 'Mathématiques', 'name_en' => 'Mathematics', 'icon' => 'o-calculator',      'color' => '#6366f1', 'coefficient' => 4],
                    ['name' => 'Physique',       'name_ar' => 'الفيزياء',     'name_fr' => 'Physique',       'name_en' => 'Physics',      'icon' => 'o-beaker',          'color' => '#3b82f6', 'coefficient' => 3],
                    ['name' => 'Français',       'name_ar' => 'الفرنسية',     'name_fr' => 'Français',       'name_en' => 'French',       'icon' => 'o-language',        'color' => '#ec4899', 'coefficient' => 3],
                    ['name' => 'Arabe',          'name_ar' => 'اللغة العربية','name_fr' => 'Arabe',          'name_en' => 'Arabic',       'icon' => 'o-book-open',       'color' => '#f59e0b', 'coefficient' => 4],
                    ['name' => 'Anglais',        'name_ar' => 'الإنجليزية',   'name_fr' => 'Anglais',        'name_en' => 'English',      'icon' => 'o-globe-alt',       'color' => '#22c55e', 'coefficient' => 2],
                    ['name' => 'Histoire-Géo',   'name_ar' => 'التاريخ والجغرافيا','name_fr' => 'Histoire-Géo','name_en' => 'History-Geo','icon' => 'o-map',            'color' => '#84cc16', 'coefficient' => 2],
                    ['name' => 'Sciences',       'name_ar' => 'علوم الطبيعة', 'name_fr' => 'Sciences',       'name_en' => 'Sciences',     'icon' => 'o-sparkles',        'color' => '#14b8a6', 'coefficient' => 2],
                    ['name' => 'Informatique',   'name_ar' => 'الإعلام الآلي','name_fr' => 'Informatique',   'name_en' => 'Computer Science','icon' => 'o-computer-desktop','color' => '#8b5cf6', 'coefficient' => 2],
                ];

                foreach ($subjects as $order => $subjectData) {
                    Subject::create(array_merge($subjectData, [
                        'grade_id' => $grade->id,
                        'order'    => $order + 1,
                    ]));
                }
            }
        }
    }
}
