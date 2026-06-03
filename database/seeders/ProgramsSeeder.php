<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use App\Models\ProgramSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProgramsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create demo individual user
        if (!User::where('email', 'individual@demo.com')->exists()) {
            User::create([
                'name'       => 'Demo Individual',
                'first_name' => 'Ahmad',
                'last_name'  => 'Learner',
                'email'      => 'individual@demo.com',
                'password'   => Hash::make('password'),
                'role'       => 'individual',
                'status'     => 'active',
                'language'   => 'en',
            ]);
        }

        // 2. Sample programs that bundle existing Islamic courses
        $beginnerCourses = Course::where('is_islamic', true)->where('level','beginner')->pluck('id')->toArray();
        $intermediateCourses = Course::where('is_islamic', true)->where('level','intermediate')->pluck('id')->toArray();
        $premiumCourses = Course::where('is_islamic', true)->where('level','premium')->pluck('id')->toArray();

        $programs = [
            [
                'data' => [
                    'title'          => 'Hafiz Program — Quran Memorization Journey',
                    'description'    => 'A complete 12-month program to memorize the Holy Quran with proper Tajweed. Bundles Beginner and Intermediate courses with weekly live sessions.',
                    'level'          => 'beginner',
                    'type'           => 'subscription',
                    'price'          => 3500,
                    'duration_weeks' => 48,
                    'capacity'       => 30,
                    'schedule_type'  => 'weekly',
                    'is_islamic'     => true,
                    'audience'       => 'both',
                    'status'         => 'published',
                ],
                'courses' => array_merge($beginnerCourses, $intermediateCourses),
                'sessions' => [
                    ['title'=>'Tajweed Recitation Class', 'day_of_week'=>'mon', 'start_time'=>'19:00', 'end_time'=>'20:30', 'recurrence'=>'weekly', 'location'=>'Online', 'meeting_link'=>'https://zoom.us/j/123'],
                    ['title'=>'Memorization Review',      'day_of_week'=>'wed', 'start_time'=>'19:00', 'end_time'=>'20:00', 'recurrence'=>'weekly', 'location'=>'Online', 'meeting_link'=>'https://zoom.us/j/124'],
                    ['title'=>'Group Recitation Circle',  'day_of_week'=>'fri', 'start_time'=>'18:00', 'end_time'=>'19:30', 'recurrence'=>'weekly', 'location'=>'Online', 'meeting_link'=>'https://zoom.us/j/125'],
                ],
            ],
            [
                'data' => [
                    'title'          => 'Aalim Program — Advanced Islamic Studies',
                    'description'    => 'A 24-month rigorous program for serious students. Covers Quran, Hadith, Fiqh, Aqidah, and Seerah at scholar level.',
                    'level'          => 'premium',
                    'type'           => 'subscription',
                    'price'          => 5500,
                    'duration_weeks' => 96,
                    'capacity'       => 15,
                    'schedule_type'  => 'weekly',
                    'is_islamic'     => true,
                    'audience'       => 'both',
                    'status'         => 'published',
                ],
                'courses' => $premiumCourses,
                'sessions' => [
                    ['title'=>'Daily Hadith Study', 'day_of_week'=>'mon', 'start_time'=>'07:00', 'end_time'=>'08:00', 'recurrence'=>'daily', 'location'=>'Online'],
                    ['title'=>'Fiqh Lecture',       'day_of_week'=>'tue', 'start_time'=>'20:00', 'end_time'=>'21:30', 'recurrence'=>'weekly', 'location'=>'Online', 'meeting_link'=>'https://zoom.us/j/200'],
                    ['title'=>'Tafsir Class',       'day_of_week'=>'thu', 'start_time'=>'20:00', 'end_time'=>'21:30', 'recurrence'=>'weekly', 'location'=>'Online', 'meeting_link'=>'https://zoom.us/j/201'],
                    ['title'=>'Q&A with Shaykh',    'day_of_week'=>'sat', 'start_time'=>'15:00', 'end_time'=>'16:30', 'recurrence'=>'biweekly', 'location'=>'Online', 'meeting_link'=>'https://zoom.us/j/202'],
                ],
            ],
            [
                'data' => [
                    'title'          => 'Quran Companion — Free Introduction',
                    'description'    => 'A free 8-week introduction to the Quran. Perfect for absolute beginners. No subscription required.',
                    'level'          => 'beginner',
                    'type'           => 'free',
                    'price'          => 0,
                    'duration_weeks' => 8,
                    'capacity'       => 100,
                    'schedule_type'  => 'weekly',
                    'is_islamic'     => true,
                    'audience'       => 'individuals',
                    'status'         => 'published',
                ],
                'courses' => array_slice($beginnerCourses, 0, 1),
                'sessions' => [
                    ['title'=>'Welcome & Arabic Letters', 'day_of_week'=>'sat', 'start_time'=>'10:00', 'end_time'=>'11:00', 'recurrence'=>'weekly', 'location'=>'Online', 'meeting_link'=>'https://zoom.us/j/300'],
                ],
            ],
        ];

        foreach ($programs as $entry) {
            if (Program::where('title', $entry['data']['title'])->exists()) continue;

            $program = Program::create($entry['data']);

            // Attach courses
            foreach ($entry['courses'] as $idx => $courseId) {
                $program->courses()->attach($courseId, [
                    'order'       => $idx + 1,
                    'is_required' => true,
                ]);
            }

            // Create sessions
            foreach ($entry['sessions'] as $idx => $session) {
                ProgramSession::create(array_merge($session, [
                    'program_id' => $program->id,
                    'order'      => $idx + 1,
                ]));
            }
        }
    }
}
