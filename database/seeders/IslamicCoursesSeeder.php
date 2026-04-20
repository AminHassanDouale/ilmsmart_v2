<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\IslamicBook;
use Illuminate\Database\Seeder;

class IslamicCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $books = IslamicBook::pluck('id', 'title')->toArray();
        $tajweedId = $books["Student's Guide to Tajweed Rules"] ?? null;
        $bagdadId  = collect($books)->filter(fn($v,$k) => str_contains($k,'البغدادية'))->first();
        $nooranId  = collect($books)->filter(fn($v,$k) => str_contains($k,'النورانية'))->first();

        $courses = [
            [
                'title'       => 'Quran Recitation — Beginner',
                'description' => 'Learn the Arabic alphabet, basic Tajweed rules, and recite short surahs correctly. Suitable for absolute beginners with no prior Arabic knowledge.',
                'level'       => 'beginner',
                'type'        => 'free',
                'price'       => 0,
                'status'      => 'published',
                'is_islamic'  => true,
                'modules' => [
                    [
                        'title' => 'Arabic Alphabet & Letters',
                        'lessons' => [
                            ['title' => 'The Arabic Letters (Huroof)', 'meta' => ['type'=>'custom'], 'content' => 'Introduction to the 28 Arabic letters and their pronunciation.'],
                            ['title' => 'Al-Qaidah Al-Baghdadiyah', 'meta' => ['type'=>'book','book_id'=>$bagdadId]],
                            ['title' => 'Practice: Letter Recognition', 'meta' => ['type'=>'custom'], 'is_free_preview' => true],
                        ],
                    ],
                    [
                        'title' => 'Tajweed Fundamentals',
                        'lessons' => [
                            ['title' => "Student's Guide to Tajweed Rules", 'meta' => ['type'=>'book','book_id'=>$tajweedId]],
                            ['title' => 'Al-Qaidah An-Nouraniah — Introduction', 'meta' => ['type'=>'book','book_id'=>$nooranId]],
                        ],
                    ],
                    [
                        'title' => 'Short Surahs (Juz Amma)',
                        'lessons' => [
                            ['title' => 'Surah Al-Fatiha', 'meta' => ['type'=>'quran_surah','surah'=>1,'name'=>'Al-Fatiha']],
                            ['title' => 'Surah Al-Ikhlas', 'meta' => ['type'=>'quran_surah','surah'=>112,'name'=>'Al-Ikhlas']],
                            ['title' => 'Surah Al-Falaq',  'meta' => ['type'=>'quran_surah','surah'=>113,'name'=>'Al-Falaq']],
                            ['title' => 'Surah An-Nas',    'meta' => ['type'=>'quran_surah','surah'=>114,'name'=>'An-Nas']],
                        ],
                    ],
                    [
                        'title' => 'Daily Morning & Evening Adhkar',
                        'lessons' => [
                            ['title' => 'Morning Adhkar', 'meta' => ['type'=>'dua','category'=>'morning']],
                            ['title' => 'Evening Adhkar', 'meta' => ['type'=>'dua','category'=>'evening']],
                        ],
                    ],
                ],
            ],
            [
                'title'       => 'Islamic Studies — Intermediate',
                'description' => 'Deepen your knowledge of Quran, Hadith, Fiqh, and Islamic history. Includes Hadith study from Sahih Al-Bukhari and Sahih Muslim.',
                'level'       => 'intermediate',
                'type'        => 'paid',
                'price'       => 2500,
                'status'      => 'published',
                'is_islamic'  => true,
                'modules' => [
                    [
                        'title' => 'Hadith Sciences',
                        'lessons' => [
                            ['title' => 'Introduction to Hadith Collections', 'meta' => ['type'=>'custom'], 'content' => 'Overview of the six major hadith collections (Kutub al-Sittah).'],
                            ['title' => 'Sahih Al-Bukhari — Revelation',      'meta' => ['type'=>'hadith','collection'=>'bukhari','page'=>1]],
                            ['title' => 'Sahih Al-Bukhari — Belief (Iman)',   'meta' => ['type'=>'hadith','collection'=>'bukhari','page'=>3]],
                            ['title' => 'Sahih Muslim — Prayer',              'meta' => ['type'=>'hadith','collection'=>'muslim','page'=>1]],
                        ],
                    ],
                    [
                        'title' => 'Quran — Juz 1 (Al-Baqarah)',
                        'lessons' => [
                            ['title' => 'Surah Al-Baqarah — Overview',      'meta' => ['type'=>'quran_surah','surah'=>2,'name'=>'Al-Baqarah']],
                            ['title' => 'Surah Al-Imran',                    'meta' => ['type'=>'quran_surah','surah'=>3,'name'=>'Al-Imran']],
                            ['title' => 'Ayat Al-Kursi (2:255) — Memorization', 'meta' => ['type'=>'custom'], 'content' => 'Memorization and tafsir of Ayat Al-Kursi, the greatest verse of the Quran.'],
                        ],
                    ],
                    [
                        'title' => 'Supplications & Dhikr',
                        'lessons' => [
                            ['title' => 'Duas After Prayer',   'meta' => ['type'=>'dua','category'=>'prayer']],
                            ['title' => 'Duas Before Sleep',   'meta' => ['type'=>'dua','category'=>'sleep']],
                            ['title' => 'Duas for Forgiveness','meta' => ['type'=>'dua','category'=>'forgiveness']],
                        ],
                    ],
                ],
            ],
            [
                'title'       => 'Complete Islamic Scholar Path — Premium',
                'description' => 'A comprehensive Islamic curriculum covering advanced Quran study (all 30 Juz), major hadith collections, Fiqh, Tafsir, Seerah, and Islamic history. For serious students.',
                'level'       => 'premium',
                'type'        => 'subscription',
                'price'       => 5000,
                'status'      => 'published',
                'is_islamic'  => true,
                'modules' => [
                    [
                        'title' => 'Advanced Tajweed & Recitation',
                        'lessons' => [
                            ['title' => 'Advanced Tajweed Rules', 'meta' => ['type'=>'book','book_id'=>$tajweedId]],
                            ['title' => 'Surah Ya-Sin',      'meta' => ['type'=>'quran_surah','surah'=>36,'name'=>'Ya-Sin']],
                            ['title' => 'Surah Al-Mulk',     'meta' => ['type'=>'quran_surah','surah'=>67,'name'=>'Al-Mulk']],
                            ['title' => 'Surah Al-Kahf',     'meta' => ['type'=>'quran_surah','surah'=>18,'name'=>'Al-Kahf']],
                            ['title' => 'Surah Al-Waqiah',   'meta' => ['type'=>'quran_surah','surah'=>56,'name'=>'Al-Waqiah']],
                        ],
                    ],
                    [
                        'title' => 'Complete Hadith Study',
                        'lessons' => [
                            ['title' => 'Sahih Al-Bukhari — Book of Knowledge', 'meta' => ['type'=>'hadith','collection'=>'bukhari','page'=>2]],
                            ['title' => 'Sahih Muslim — Purification',          'meta' => ['type'=>'hadith','collection'=>'muslim','page'=>2]],
                            ['title' => 'Jami At-Tirmidhi — Chapters on Zuhd', 'meta' => ['type'=>'hadith','collection'=>'tirmidhi','page'=>1]],
                            ['title' => 'Sunan Abu Dawud — Prayer',             'meta' => ['type'=>'hadith','collection'=>'abudawud','page'=>1]],
                        ],
                    ],
                    [
                        'title' => 'Fiqh & Daily Islamic Practice',
                        'lessons' => [
                            ['title' => 'Conditions of Prayer', 'meta' => ['type'=>'custom'], 'content' => 'Detailed study of the pillars, conditions, and invalidators of the five daily prayers.'],
                            ['title' => 'Zakah & Fasting',      'meta' => ['type'=>'custom'], 'content' => 'Rules of Zakah calculation and the obligations of Ramadan fasting.'],
                            ['title' => 'Daily Comprehensive Adhkar', 'meta' => ['type'=>'dua','category'=>'general']],
                            ['title' => 'Duas for All Occasions',     'meta' => ['type'=>'dua','category'=>'travel']],
                        ],
                    ],
                    [
                        'title' => 'Seerah & Islamic History',
                        'lessons' => [
                            ['title' => 'The Life of Prophet Muhammad ﷺ', 'meta' => ['type'=>'custom'], 'content' => 'Comprehensive study of the Prophet\'s life, character, and mission.'],
                            ['title' => 'The Rightly-Guided Caliphs', 'meta' => ['type'=>'custom'], 'content' => 'History of Abu Bakr, Umar, Uthman, and Ali (رضي الله عنهم).'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $modules = $courseData['modules'];
            unset($courseData['modules']);

            // Skip if already exists
            if (Course::where('title', $courseData['title'])->where('is_islamic', true)->exists()) {
                continue;
            }

            $course = Course::create($courseData);

            foreach ($modules as $modOrder => $modData) {
                $lessons = $modData['lessons'];
                unset($modData['lessons']);
                $modData['course_id'] = $course->id;
                $modData['order']     = $modOrder + 1;

                $module = Module::create($modData);

                foreach ($lessons as $lessonOrder => $lessonData) {
                    Lesson::create([
                        'module_id'        => $module->id,
                        'title'            => $lessonData['title'],
                        'content'          => $lessonData['content'] ?? null,
                        'type'             => 'text',
                        'lesson_meta'      => $lessonData['meta'] ?? ['type' => 'custom'],
                        'is_free_preview'  => $lessonData['is_free_preview'] ?? false,
                        'duration_minutes' => $lessonData['duration'] ?? 15,
                        'status'           => 'published',
                        'order'            => $lessonOrder + 1,
                    ]);
                }
            }
        }
    }
}
