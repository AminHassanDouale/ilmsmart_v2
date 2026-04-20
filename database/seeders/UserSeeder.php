<?php

namespace Database\Seeders;

use App\Models\{User, Student, Teacher, ParentModel, Grade, AcademicYear, Subject};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $year  = AcademicYear::where('is_current', true)->first();
        $grade = Grade::first();

        // ─── Admin ───────────────────────────────────────────────
        User::create([
            'name'       => 'Admin Système',
            'first_name' => 'Admin',
            'last_name'  => 'Système',
            'username'   => 'admin',
            'email'      => 'admin@edu.dz',
            'password'   => bcrypt('password'),
            'role'       => 'admin',
            'status'     => 'active',
            'language'   => 'fr',
        ]);

        // ─── Teacher ─────────────────────────────────────────────
        $teacherUser = User::create([
            'name'       => 'Mohammed Salah',
            'first_name' => 'Mohammed',
            'last_name'  => 'Salah',
            'username'   => 'teacher1',
            'email'      => 'teacher@edu.dz',
            'password'   => bcrypt('password'),
            'role'       => 'teacher',
            'status'     => 'active',
            'language'   => 'ar',
        ]);

        $teacher = Teacher::create([
            'user_id'          => $teacherUser->id,
            'bio'              => 'Professeur de Mathématiques avec 10 ans d\'expérience.',
            'qualification'    => 'Master en Mathématiques',
            'experience_years' => 10,
            'hourly_rate'      => 1500,
            'rating'           => 4.8,
            'rating_count'     => 25,
            'is_verified'      => true,
        ]);

        // Attach subjects
        $mathSubjects = Subject::where('name', 'Mathématiques')->take(3)->get();
        $teacher->subjects()->attach($mathSubjects->pluck('id'));

        // ─── Tutor ───────────────────────────────────────────────
        $tutorUser = User::create([
            'name'       => 'Amina Benali',
            'first_name' => 'Amina',
            'last_name'  => 'Benali',
            'username'   => 'tutor1',
            'email'      => 'tutor@edu.dz',
            'password'   => bcrypt('password'),
            'role'       => 'tutor',
            'status'     => 'active',
            'language'   => 'fr',
        ]);

        Teacher::create([
            'user_id'          => $tutorUser->id,
            'bio'              => 'Tutrice spécialisée en Français et Anglais.',
            'qualification'    => 'Licence en Lettres',
            'experience_years' => 5,
            'hourly_rate'      => 1200,
            'is_tutor'         => true,
            'is_verified'      => true,
        ]);

        // ─── Parent ───────────────────────────────────────────────
        $parentUser = User::create([
            'name'       => 'Karim Boudiaf',
            'first_name' => 'Karim',
            'last_name'  => 'Boudiaf',
            'username'   => 'parent1',
            'email'      => 'parent@edu.dz',
            'password'   => bcrypt('password'),
            'role'       => 'parent',
            'status'     => 'active',
            'language'   => 'fr',
        ]);

        $parent = ParentModel::create([
            'user_id'    => $parentUser->id,
            'occupation' => 'Ingénieur',
            'address'    => 'Alger, Algérie',
        ]);

        // ─── Student ─────────────────────────────────────────────
        $studentUser = User::create([
            'name'       => 'Yasser Boudiaf',
            'first_name' => 'Yasser',
            'last_name'  => 'Boudiaf',
            'username'   => 'student1',
            'email'      => 'student@edu.dz',
            'password'   => bcrypt('password'),
            'role'       => 'student',
            'status'     => 'active',
            'language'   => 'ar',
        ]);

        $student = Student::create([
            'user_id'        => $studentUser->id,
            'grade_id'       => $grade?->id,
            'academic_year_id' => $year?->id,
            'student_number' => 'STU-2024-001',
            'birth_date'     => '2010-03-15',
            'gender'         => 'male',
            'school_name'    => 'CEM Ibn Khaldoun',
            'student_type'   => 'school',
        ]);

        // Link student to parent
        $parent->students()->attach($student->id, ['relationship' => 'father']);

        // ─── Individual Student ───────────────────────────────────
        $indivUser = User::create([
            'name'       => 'Sara Meziani',
            'first_name' => 'Sara',
            'last_name'  => 'Meziani',
            'username'   => 'student2',
            'email'      => 'sara@edu.dz',
            'password'   => bcrypt('password'),
            'role'       => 'student',
            'status'     => 'active',
            'language'   => 'fr',
        ]);

        Student::create([
            'user_id'        => $indivUser->id,
            'student_number' => 'STU-2024-002',
            'birth_date'     => '2008-07-22',
            'gender'         => 'female',
            'student_type'   => 'individual',
        ]);
    }
}
